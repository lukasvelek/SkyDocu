<?php

namespace App\Managers\Container;

use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\DocumentClassRepository;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\Container\FolderRepository;
use App\Repositories\Container\GroupRepository;

class DocumentManager extends AManager {
    public DocumentRepository $documentRepository;
    private DocumentClassRepository $documentClassRepository;
    private GroupRepository $groupRepository;
    private FolderRepository $folderRepository;
    public EnumManager $enumManager;

    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        DocumentRepository $documentRepository,
        DocumentClassRepository $documentClassRepository,
        GroupRepository $groupRepository,
        FolderRepository $folderRepository
    ) {
        parent::__construct($logger, $entityManager);

        $this->documentRepository = $documentRepository;
        $this->documentClassRepository = $documentClassRepository;
        $this->groupRepository = $groupRepository;
        $this->folderRepository = $folderRepository;
    }

    public function composeQueryForDocuments(string $userId, string $folderId, bool $allMetadata) {
        $qb = $this->documentRepository->composeQueryForDocuments();

        $qb->andWhere('folderId = ?', [$folderId]);

        $groupIds = $this->groupRepository->getGroupsForUser($userId);

        $classes = $this->documentClassRepository->getVisibleClassesForGroups($groupIds);

        if(empty($classes)) {
            $qb->andWhere('1=0');
        } else {
            $qb->andWhere($qb->getColumnInValues('classId', $classes));
        }

        $sharedDocumentIds = $this->documentRepository->getSharedDocumentsForUser($userId);
        
        if(!empty($sharedDocumentIds)) {
            $qb->orWhere($qb->getColumnInValues('documentId', $sharedDocumentIds));
        }

        if($allMetadata) {
            $visibleCustomMetadataIds = $this->folderRepository->getVisibleCustomMetadataIdForFolder($folderId);

            $visibleCustomMetadata = [];
            foreach($visibleCustomMetadataIds as $metadataId) {
                $visibleCustomMetadata[] = $this->folderRepository->getCustomMetadataById($metadataId);
            }
        }

        return $qb;
    }

    public function composeQueryForSharedDocuments(string $userId) {
        $qb = $this->documentRepository->composeQueryForDocuments();

        $sharedDocumentIds = $this->documentRepository->getSharedDocumentsForUser($userId);

        $qb->andWhere($qb->getColumnInValues('documentId', $sharedDocumentIds));

        $groupIds = $this->groupRepository->getGroupsForUser($userId);

        $classes = $this->documentClassRepository->getVisibleClassesForGroups($groupIds);

        if(empty($classes)) {
            $qb->andWhere('1=0');
        } else {
            $qb->andWhere($qb->getColumnInValues('classId', $classes));
        }

        $sharedDocumentIds = $this->documentRepository->getSharedDocumentsForUser($userId);
        
        if(!empty($sharedDocumentIds)) {
            $qb->orWhere($qb->getColumnInValues('documentId', $sharedDocumentIds));
        }

        return $qb;
    }

    public function getCustomMetadataForFolder(string $folderId) {
        $metadataIds = $this->folderRepository->getVisibleCustomMetadataIdForFolder($folderId);

        $metadatas = [];
        foreach($metadataIds as $metadataId) {
            $row = $this->folderRepository->getCustomMetadataById($metadataId);
            $row = DatabaseRow::createFromDbRow($row);
            $metadatas[$metadataId] = $row;
        }

        return $metadatas;
    }

    public function getMetadataValues(string $metadataId) {
        $cache = $this->cacheFactory->getCache(CacheNames::METADATA_VALUES);

        return $cache->load($metadataId, function() use ($metadataId) {
            return $this->documentRepository->getMetadataValues($metadataId);
        });
    }

    public function composeQueryForDocumentCustomMetadataValues() {
        return $this->documentRepository->composeQueryForDocumentCustomMetadataValues();
    }

    public function getDocumentClassesForDocumentCreateForUser(string $userId) {
        $groups = $this->groupRepository->getGroupsForUser($userId);

        $classes = [];
        foreach($groups as $groupId) {
            $qb = $this->documentClassRepository->composeQueryForClassesForGroup($groupId)
                ->andWhere('canView = 1')
                ->andWhere('canCreate = 1')
                ->execute();

            while($row = $qb->fetchAssoc()) {
                $row = DatabaseRow::createFromDbRow($row);

                $class = $this->documentClassRepository->getDocumentClassById($row->classId);

                $classes[$row->classId] = $class['title'];
            }
        }

        return $classes;
    }

    public function getAllDocumentClassesForUser(string $userId) {
        $groups = $this->groupRepository->getGroupsForUser($userId);

        $classes = [];
        foreach($groups as $groupId) {
            $qb = $this->documentClassRepository->composeQueryForClassesForGroup($groupId)
                ->execute();

            while($row = $qb->fetchAssoc()) {
                $row = DatabaseRow::createFromDbRow($row);

                $classes[$row->classId] = [
                    'canView' => $row->canView,
                    'canCreate' => $row->canCreate,
                    'canEdit' => $row->canEdit,
                    'canDelete' => $row->canDelete
                ];
            }
        }
    }

    public function createNewDocument(array $metadataValues, array $customMetadataValues) {
        $documentId = $this->createId(EntityManager::C_DOCUMENTS);

        if(!$this->documentRepository->createNewDocument($documentId, $metadataValues)) {
            throw new GeneralException('Database error.');
        }

        foreach($customMetadataValues as $metadataId => $value) {
            $entryId = $this->createId(EntityManager::C_DOCUMENTS_CUSTOM_METADATA);

            $data = [
                'documentId' => $documentId,
                'metadataId' => $metadataId,
                'value' => $value
            ];

            if(!$this->documentRepository->createNewCustomMetadataEntry($entryId, $data)) {
                throw new GeneralException('Database error.');
            }
        }
    }

    public function getDocumentCountForFolder(string $folderId) {
        $qb = $this->documentRepository->composeQueryForDocuments();
        $qb->andWhere('folderId = ?', [$folderId])
            ->select(['COUNT(*) AS cnt'])
            ->execute()
        ;

        return $qb->fetch('cnt');
    }

    /**
     * Gets multiple documents by their IDs. This method does not obtain custom metadata for each document.
     * 
     * @param array $documentIds Document IDs
     * @return array<string, DatabaseRow> Array of documents where index is the document ID and the value is the DatabaseRow
     */
    public function getDocumentsByIds(array $documentIds) {
        $docuRows = $this->documentRepository->getDocumentsByIds($documentIds);

        $tmp = [];
        foreach($docuRows as $docuRow) {
            if($docuRow === null) {
                continue;
            }
    
            $docuRow = DatabaseRow::createFromDbRow($docuRow);

            $tmp[$docuRow->documentId] = $docuRow;
        }

        return $tmp;
    }
    
    public function getDocumentById(string $documentId, bool $allMetadata = true) {
        $docuRow = $this->documentRepository->getDocumentById($documentId);

        if($docuRow === null) {
            throw new NonExistingEntityException('Document does not exist.');
        }

        $docuRow = DatabaseRow::createFromDbRow($docuRow);

        if($allMetadata && $docuRow->folderId !== null) {
            /**
             * @var array<string, \App\Core\DB\DatabaseRow> $customMetadatas
             */
            $customMetadatas = $this->getCustomMetadataForFolder($docuRow->folderId);

            $documentCustomMetadataValues = [];
            foreach($customMetadatas as $metadataId => $metadata) {
                $qb = $this->composeQueryForDocumentCustomMetadataValues();
                $qb->andWhere('metadataId = ?', [$metadataId])
                    ->andWhere('documentId = ?', [$documentId])
                    ->execute();

                while($metaValueRow = $qb->fetchAssoc()) {
                    $metaValueRow = DatabaseRow::createFromDbRow($metaValueRow);

                    $documentCustomMetadataValues[$metadata->title] = $metaValueRow->value;
                }

                if(array_key_exists($metadata->title, $documentCustomMetadataValues)) {
                    $docuRow->{$metadata->title} = $documentCustomMetadataValues[$metadata->title];
                } else {
                    $docuRow->{$metadata->title} = null;
                }
            }
        }

        return $docuRow;
    }

    public function updateDocument(string $documentId, array $data) {
        $data['dateModified'] = DateTime::now();
        
        if(!$this->documentRepository->updateDocument($documentId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    public function updateDocumentCustom(string $documentId, array $data) {

    }

    /**
     * Returns an array of documents or document IDs that have been shared to the given $userId
     * 
     * @param string $userId User ID
     * @param bool $returnObjects True if document objects should be returned, or false if only document IDs should be returned
     * @return array
     */
    public function getSharedDocumentsForUser(string $userId, bool $returnObjects = true) {
        $documents = $this->documentRepository->getSharedDocumentsForUser($userId);

        if($returnObjects) {
            $documents = $this->getDocumentsByIds($documents);
        }

        return $documents;
    }

    /**
     * Returns an array of documents or document IDs that have been shared by the given $userId
     * 
     * @param string $userId User ID
     * @param bool $returnObjects True if document objects should be returned, or false if only document IDs should be returned
     * @return array
     */
    public function getSharedDocumentsByUser(string $userId, bool $returnObjects = true) {
        $documents = $this->documentRepository->getSharedDocumentsByUser($userId);

        if($returnObjects) {
            $documents = $this->getDocumentsByIds($documents);
        }

        return $documents;
    }

    public function getSharesForDocumentIdsByUserId(array $documentIds, string $userId) {
        $rows = $this->documentRepository->getSharesForDocumentIdsByUserId($documentIds, $userId);

        $shares = [];
        foreach($rows as $row) {
            $shares[] = DatabaseRow::createFromDbRow($row);
        }

        return $shares;
    }

    public function shareDocument(string $documentId, string $sharedByUserId, string $sharedToUserId) {
        $sharedUntil = new DateTime();
        $sharedUntil->modify('+7d');
        $sharedUntil = $sharedUntil->getResult();

        $sharingId = $this->createId(EntityManager::C_DOCUMENT_SHARING);

        if(!$this->documentRepository->createNewDocumentSharing($sharingId, $documentId, $sharedByUserId, $sharedToUserId, $sharedUntil)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    /**
     * Removes document from folder
     * 
     * @param string $documentId
     */
    public function removeDocumentFromFolder(string $documentId) {
        $this->updateDocument($documentId, ['folderId' => null]);
    }
}

?>