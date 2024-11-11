<?php

namespace App\Managers\Container;

use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
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
            throw new GeneralException('Could not create new metadata.');
        }

        return $metadataId;
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
            throw new GeneralException('Could not create metadata folder relation.');
        }

        return $relationId;
    }
}

?>