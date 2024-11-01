<?php

namespace App\Repositories\Container;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\ARepository;

class FolderRepository extends ARepository {
    public function __construct(DatabaseConnection $conn, Logger $logger) {
        parent::__construct($conn, $logger);
    }

    public function composeQueryForFolders() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('document_folders');

        return $qb;
    }

    public function composeQueryForFolderIdsForGroup(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['folderId'])
            ->from('document_folder_group_relation')
            ->where('groupId = ?', [$groupId]);

        return $qb;
    }

    public function getVisibleFolderIdsForGroup(string $groupId) {
        $qb = $this->composeQueryForFolderIdsForGroup($groupId);
        $qb->andWhere('canView = 1')
            ->execute();

        $folders = [];
        while($row = $qb->fetchAssoc()) {
            $folders[] = $row['folderId'];
        }

        return $folders;
    }

    public function getFolderById(string $folderId) {
        $qb = $this->composeQueryForFolders();
        $qb->andWhere('folderId = ?', [$folderId])
            ->execute();

        return $qb->fetch();
    }

    public function getVisibleCustomMetadataForFolder(string $folderId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['customMetadataId'])
            ->from('document_folder_custom_metadata_relation')
            ->where('folderId = ?', [$folderId])
            ->execute();

        $metadata = [];
        while($row = $qb->fetchAssoc()) {
            $metadata[] = $row['customMetadataId'];
        }

        return $metadata;
    }
}

?>