<?php

namespace App\Managers\Container;

use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\DocumentClassRepository;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\Container\FolderRepository;
use App\Repositories\Container\GroupRepository;

class DocumentManager extends AManager {
    private DocumentRepository $dr;
    private DocumentClassRepository $dcr;
    private GroupRepository $gr;
    private FolderRepository $fr;

    public function __construct(Logger $logger, EntityManager $entityManager, DocumentRepository $dr, DocumentClassRepository $dcr, GroupRepository $gr, FolderRepository $fr) {
        parent::__construct($logger, $entityManager);

        $this->dr = $dr;
        $this->dcr = $dcr;
        $this->gr = $gr;
        $this->fr = $fr;
    }

    public function composeQueryForDocuments(string $userId, string $folderId, bool $allMetadata) {
        $qb = $this->dr->composeQueryForDocuments();

        $qb->andWhere('folderId = ?', [$folderId]);

        $groupIds = $this->gr->getGroupsForUser($userId);

        $classes = [];
        foreach($groupIds as $groupId) {
            $classesTmp = $this->dcr->getVisibleClassesForGroup($groupId);

            foreach($classesTmp as $classId) {
                if(!in_array($classId, $classes)) {
                    $classes[] = $classId;
                }
            }
        }

        if(empty($classes)) {
            $qb->andWhere('1=0');
        } else {
            $qb->andWhere($qb->getColumnInValues('classId', $classes));
        }

        $sharedDocumentIds = $this->dr->getSharedDocumentsForUser($userId);
        
        if(!empty($sharedDocumentIds)) {
            $qb->orWhere($qb->getColumnInValues('documentId', $sharedDocumentIds));
        }

        if($allMetadata) {
            $visibleCustomMetadataIds = $this->fr->getVisibleCustomMetadataIdForFolder($folderId);

            $visibleCustomMetadata = [];
            foreach($visibleCustomMetadataIds as $metadataId) {
                $visibleCustomMetadata[] = $this->fr->getCustomMetadataById($metadataId);
            }
        }

        return $qb;
    }

    public function getCustomMetadataForFolder(string $folderId) {
        $metadataIds = $this->fr->getVisibleCustomMetadataIdForFolder($folderId);

        $metadatas = [];
        foreach($metadataIds as $metadataId) {
            $row = $this->fr->getCustomMetadataById($metadataId);
            $row = DatabaseRow::createFromDbRow($row);
            $metadatas[$metadataId] = $row;
        }

        return $metadatas;
    }

    public function getMetadataValues(string $metadataId) {
        $cache = $this->cacheFactory->getCache(CacheNames::METADATA_VALUES);

        return $cache->load($metadataId, function() use ($metadataId) {
            return $this->dr->getMetadataValues($metadataId);
        });
    }

    public function composeQueryForDocumentCustomMetadataValues() {
        return $this->dr->composeQueryForDocumentCustomMetadataValues();
    }

    public function getDocumentClassesForDocumentCreateForUser(string $userId) {
        $groups = $this->gr->getGroupsForUser($userId);

        $classes = [];
        foreach($groups as $groupId) {
            $qb = $this->dcr->composeQueryForClassesForGroup($groupId)
                ->andWhere('canView = 1')
                ->andWhere('canCreate = 1')
                ->execute();

            while($row = $qb->fetchAssoc()) {
                $row = DatabaseRow::createFromDbRow($row);

                $class = $this->dcr->getDocumentClassById($row->classId);

                $classes[$row->classId] = $class['title'];
            }
        }

        return $classes;
    }

    public function getAllDocumentClassesForUser(string $userId) {
        $groups = $this->gr->getGroupsForUser($userId);

        $classes = [];
        foreach($groups as $groupId) {
            $qb = $this->dcr->composeQueryForClassesForGroup($groupId)
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

        if(!$this->dr->createNewDocument($documentId, $metadataValues)) {
            throw new GeneralException('Database error while creating new document.');
        }

        foreach($customMetadataValues as $metadataId => $value) {
            $entryId = $this->createId(EntityManager::C_DOCUMENTS_CUSTOM_METADATA);

            $data = [
                'documentId' => $documentId,
                'metadataId' => $metadataId,
                'value' => $value
            ];

            if(!$this->dr->createNewCustomMetadataEntry($entryId, $data)) {
                throw new GeneralException('Database error while saving custom metadata.');
            }
        }
    }

    public function getDocumentCountForFolder(string $folderId) {
        $qb = $this->dr->composeQueryForDocuments();
        $qb->andWhere('folderId = ?', [$folderId])
            ->select(['COUNT(*) AS cnt'])
            ->execute()
        ;

        return $qb->fetch('cnt');
    }
}

?>