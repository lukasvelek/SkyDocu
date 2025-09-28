<?php

namespace App\Managers\Container;

use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Repositories\Container\FolderRepository;
use App\Repositories\Container\MetadataRepository;

class MetadataManager extends AManager {
    private MetadataRepository $metadataRepository;
    private FolderRepository $folderRepository;

    public function __construct(Logger $logger, MetadataRepository $metadataRepository, FolderRepository $folderRepository) {
        parent::__construct($logger);

        $this->metadataRepository = $metadataRepository;
        $this->folderRepository = $folderRepository;
    }
    
    public function createNewMetadata(string $title, string $guiTitle, int $type, ?string $defaultValue, bool $isRequired) {
        $metadataId = $this->createId();

        $data = [
            'title' => $title,
            'guiTitle' => $guiTitle,
            'type' => $type,
            'isRequired' => ($isRequired ? 1 : 0),
            'metadataId' => $metadataId
        ];

        if($defaultValue !== null) {
            $data['defaultValue'] = $defaultValue;
        }

        if(!$this->metadataRepository->createNewMetadata($data)) {
            throw new GeneralException('Database error.');
        }

        return $metadataId;
    }

    public function updateMetadata(string $metadataId, array $data) {
        if(!$this->metadataRepository->updateMetadata($metadataId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    public function getFoldersWithoutMetadataRights(string $metadataId) {
        $qb = $this->metadataRepository->composeQueryForMetadataFolderRights();
        $qb->andWhere('customMetadataId = ?', [$metadataId]);
        $qb->execute();

        $folders = [];
        while($row = $qb->fetchAssoc()) {
            $folders[] = $row['folderId'];
        }

        $qb = $this->folderRepository->composeQueryForFolders();
        $qb->andWhere($qb->getColumnNotInValues('folderId', $folders));
        $qb->execute();

        $folders = [];
        while($row = $qb->fetchAssoc()) {
            $folder = $this->folderRepository->getFolderById($row['folderId']);

            if($folder === null) {
                continue;
            }

            $folders[$row['folderId']] = DatabaseRow::createFromDbRow($folder);
        }

        return $folders;
    }

    public function createMetadataFolderRight(string $metadataId, string $folderId) {
        $relationId = $this->createId();

        if(!$this->metadataRepository->createNewMetadataFolderRight($relationId, $metadataId, $folderId)) {
            throw new GeneralException('Database error.');
        }

        return $relationId;
    }

    public function removeMetadataFolderRight(string $metadataId, string $folderId) {
        if(!$this->metadataRepository->removeMetadataFolderRight($metadataId, $folderId)) {
            throw new GeneralException('Database error.');
        }
    }

    public function createMetadataEnumValue(string $metadataId, string $title) {
        $valueId = $this->createId();

        $data = [
            'valueId' => $valueId,
            'metadataId' => $metadataId,
            'title' => $title
        ];
        
        $lastKey = $this->metadataRepository->getLastMetadataEnumValueKey($metadataId);
        if($lastKey !== null) {
            $data['metadataKey'] = ((int)$lastKey) + 1;
        }

        if(!$this->metadataRepository->createNewMetadataEnumValue($data)) {
            throw new GeneralException('Database error.');
        }

        $this->cacheFactory->invalidateCacheByNamespace(CacheNames::METADATA_VALUES);
    }

    public function updateMetadataEnumValue(string $valueId, array $data) {
        if(!$this->metadataRepository->updateMetadataEnumValue($valueId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->cacheFactory->invalidateCacheByNamespace(CacheNames::METADATA_VALUES);
    }
    
    public function isMetadataEnumValueUsed(string $valueId, string $metadataId) {
        $row = $this->metadataRepository->getMetadataEnumValueById($valueId);

        if($row === null) {
            throw new GeneralException('Enum value does not exist.');
        }

        $row = DatabaseRow::createFromDbRow($row);

        $usages = $this->metadataRepository->getMetadataEnumValueUsage($metadataId, $row->metadataKey);

        return $usages->num_rows > 0;
    }

    public function deleteMetadataEnumValue(string $valueId) {
        if(!$this->deleteMetadataEnumValue($valueId)) {
            throw new GeneralException('Database error.');
        }

        $this->cacheFactory->invalidateCacheByNamespace(CacheNames::METADATA_VALUES);
    }

    public function getMetadataById(string $metadataId) {
        $metadata = $this->metadataRepository->getMetadataById($metadataId);

        if($metadata === null) {
            throw new NonExistingEntityException('Metadata does not exist.');
        }

        return DatabaseRow::createFromDbRow($metadata);
    }

    public function getMetadataForFolder(string $folderId) {
        $qb = $this->metadataRepository->composeQueryForMetadataFolderRights();
        $qb->andWhere('folderId = ?', [$folderId]);
        $qb->execute();

        $metadatas = [];
        while($row = $qb->fetchAssoc()) {
            $metadataId = $row['customMetadataId'];
            
            try {
                $metadata = $this->getMetadataById($metadataId);
            } catch(AException $e) {
                continue;
            }

            $metadatas[$metadata->title] = $metadata;
        }

        return $metadatas;
    }
    
    public function composeQueryForMetadataForFolder(string $folderId) {
        $rightsQb = $this->metadataRepository->composeQueryForMetadataFolderRights()
            ->andWhere('folderId = ?', [$folderId])
            ->select(['customMetadataId']);

        $qb = $this->metadataRepository->composeQueryForMetadata();
        $qb->andWhere('metadataId IN (' . $rightsQb->getSQL() . ')');

        return $qb;
    }

    public function getMetadataEnumValues(string $metadataId) {
        $qb = $this->metadataRepository->composeQueryMetadataEnumValues($metadataId);
        $qb->execute();

        $values = [];
        while($row = $qb->fetchAssoc()) {
            $values[$row['metadataKey']] = $row['title'];
        }

        return $values;
    }

    public function composeQueryForMetadataNotInFolder(string $folderId) {
        $rightsQb = $this->metadataRepository->composeQueryForMetadataFolderRights()
            ->andWhere('folderId = ?', [$folderId])
            ->select(['customMetadataId']);

        $qb = $this->metadataRepository->composeQueryForMetadata();
        $qb->andWhere('metadataId NOT IN (' . $rightsQb->getSQL() . ')');

        return $qb;
    }
}

?>