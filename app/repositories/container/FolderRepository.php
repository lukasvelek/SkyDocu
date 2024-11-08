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

    public function getVisibleCustomMetadataIdForFolder(string $folderId) {
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

    public function getCustomMetadataById(string $metadataId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('custom_metadata')
            ->where('metadataId = ?', [$metadataId])
            ->execute();

        return $qb->fetch();
    }

    public function getGroupFolderRelationId(string $groupId, string $folderId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['relationId'])
            ->from('document_folder_group_relation')
            ->where('folderId = ?', [$folderId])
            ->andWhere('groupId = ?', [$groupId])
            ->execute();

        return $qb->fetch('relationId');
    }

    public function insertGroupFolderRelation(string $relationId, array $data) {
        $qb = $this->qb(__METHOD__);

        $keys = [];
        $values = [];
        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        if(!in_array('relationId', $keys)) {
            $keys[] = 'relationId';
            $values[] = $relationId;
        }

        $qb->insert('document_folder_group_relation', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function updateGroupFolderRelation(string $relationId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('document_folder_group_relation')
            ->set($data)
            ->where('relationId = ?', [$relationId])
            ->execute();

        return $qb->fetchBool();
    }

    public function createNewFolder(string $folderId, string $title) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('document_folders', ['folderId', 'title'])
            ->values([$folderId, $title])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForGroupRightsInFolder(string $folderId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('document_folder_group_relation')
            ->where('folderId = ?', [$folderId]);

        return $qb;
    }
}

?>