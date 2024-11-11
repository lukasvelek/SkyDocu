<?php

namespace App\Repositories\Container;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\ARepository;

class MetadataRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function composeQueryForMetadata() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('custom_metadata');

        return $qb;
    }

    public function createNewMetadata(array $data) {
        $qb = $this->qb(__METHOD__);

        $keys = [];
        $values = [];
        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        $qb->insert('custom_metadata', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForMetadataFolderRights() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('document_folder_custom_metadata_relation');

        return $qb;
    }

    public function createNewMetadataFolderRight(string $relationId, string $metadataId, string $folderId) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('document_folder_custom_metadata_relation', ['relationId', 'customMetadataId', 'folderId'])
            ->values([$relationId, $metadataId, $folderId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>