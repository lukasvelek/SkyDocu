<?php

namespace App\Managers\Container;

use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\FolderRepository;
use App\Repositories\Container\MetadataRepository;

class MetadataManager extends AManager {
    private MetadataRepository $mr;
    private FolderRepository $fr;

    public function __construct(Logger $logger, EntityManager $em, MetadataRepository $mr, FolderRepository $fr) {
        parent::__construct($logger, $em);

        $this->mr = $mr;
        $this->fr = $fr;
    }
    
    public function createNewMetadata(string $title, string $guiTitle, int $type, ?string $defaultValue, bool $isRequired) {
        $metadataId = $this->createId(EntityManager::C_CUSTOM_METADATA);

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

        if(!$this->mr->createNewMetadata($data)) {
            throw new GeneralException('Database error.');
        }

        return $metadataId;
    }

    public function updateMetadata(string $metadataId, array $data) {
        if(!$this->mr->updateMetadata($metadataId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    public function getFoldersWithoutMetadataRights(string $metadataId) {
        $qb = $this->mr->composeQueryForMetadataFolderRights();
        $qb->andWhere('customMetadataId = ?', [$metadataId]);
        $qb->execute();

        $folders = [];
        while($row = $qb->fetchAssoc()) {
            $folders[] = $row['folderId'];
        }

        $qb = $this->fr->composeQueryForFolders();
        $qb->andWhere($qb->getColumnNotInValues('folderId', $folders));
        $qb->execute();

        $folders = [];
        while($row = $qb->fetchAssoc()) {
            $folder = $this->fr->getFolderById($row['folderId']);

            if($folder === null) {
                continue;
            }

            $folders[$row['folderId']] = DatabaseRow::createFromDbRow($folder);
        }

        return $folders;
    }

    public function createMetadataFolderRight(string $metadataId, string $folderId) {
        $relationId = $this->createId(EntityManager::C_CUSTOM_METADATA_FOLDER_RELATION);

        if(!$this->mr->createNewMetadataFolderRight($relationId, $metadataId, $folderId)) {
            throw new GeneralException('Database error.');
        }

        return $relationId;
    }

    public function removeMetadataFolderRight(string $metadataId, string $folderId) {
        if(!$this->mr->removeMetadataFolderRight($metadataId, $folderId)) {
            throw new GeneralException('Database error.');
        }
    }

    public function createMetadataEnumValue(string $metadataId, string $title) {
        $valueId = $this->createId(EntityManager::C_CUSTOM_METADATA_LIST_VALUES);

        $data = [
            'valueId' => $valueId,
            'metadataId' => $metadataId,
            'title' => $title
        ];
        
        $lastKey = $this->mr->getLastMetadataEnumValueKey($metadataId);
        if($lastKey !== null) {
            $data['metadataKey'] = ((int)$lastKey) + 1;
        }

        if(!$this->mr->createNewMetadataEnumValue($data)) {
            throw new GeneralException('Database error.');
        }

        $this->cacheFactory->invalidateCacheByNamespace(CacheNames::METADATA_VALUES);
    }

    public function updateMetadataEnumValue(string $valueId, array $data) {
        if(!$this->mr->updateMetadataEnumValue($valueId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->cacheFactory->invalidateCacheByNamespace(CacheNames::METADATA_VALUES);
    }
    
    public function isMetadataEnumValueUsed(string $valueId, string $metadataId) {
        $row = $this->mr->getMetadataEnumValueById($valueId);

        if($row === null) {
            throw new GeneralException('Enum value does not exist.');
        }

        $row = DatabaseRow::createFromDbRow($row);

        $usages = $this->mr->getMetadataEnumValueUsage($metadataId, $row->metadataKey);

        return $usages->num_rows > 0;
    }

    public function deleteMetadataEnumValue(string $valueId) {
        if(!$this->deleteMetadataEnumValue($valueId)) {
            throw new GeneralException('Database error.');
        }

        $this->cacheFactory->invalidateCacheByNamespace(CacheNames::METADATA_VALUES);
    }

    public function getMetadataById(string $metadataId) {
        $metadata = $this->mr->getMetadataById($metadataId);

        if($metadata === null) {
            throw new NonExistingEntityException('Metadata does not exist.');
        }

        return DatabaseRow::createFromDbRow($metadata);
    }

    public function getMetadataForFolder(string $folderId) {
        $qb = $this->mr->composeQueryForMetadataFolderRights();
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
        $rightsQb = $this->mr->composeQueryForMetadataFolderRights()
            ->andWhere('folderId = ?', [$folderId])
            ->select(['customMetadataId']);

        $qb = $this->mr->composeQueryForMetadata();
        $qb->andWhere('metadataId IN (' . $rightsQb->getSQL() . ')');

        return $qb;
    }

    public function getMetadataEnumValues(string $metadataId) {
        $qb = $this->mr->composeQueryMetadataEnumValues($metadataId);
        $qb->execute();

        $values = [];
        while($row = $qb->fetchAssoc()) {
            $values[$row['metadataKey']] = $row['title'];
        }

        return $values;
    }
}

?>