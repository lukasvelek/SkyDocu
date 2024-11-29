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

    public function updateMetadata(string $metadataId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('custom_metadata')
            ->set($data)
            ->where('metadataId = ?', [$metadataId])
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

    public function removeMetadataFolderRight(string $metadataId, string $folderId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('document_folder_custom_metadata_relation')
            ->where('customMetadataId = ?', [$metadataId])
            ->andWhere('folderId = ?', [$folderId])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryMetadataEnumValues(string $metadataId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('custom_metadata_list_values')
            ->where('metadataId = ?', [$metadataId]);

        return $qb;
    }

    public function getLastMetadataEnumValueKey(string $metadataId) {
        $qb = $this->composeQueryMetadataEnumValues($metadataId);
        $qb->select(['metadataKey'])
            ->orderBy('metadataKey', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch('metadataKey');
    }

    public function createNewMetadataEnumValue(array $data) {
        $keys = [];
        $values = [];
        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        $qb = $this->qb(__METHOD__);

        $qb->insert('custom_metadata_list_values', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function updateMetadataEnumValue(string $valueId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('custom_metadata_list_values')
            ->set($data)
            ->where('valueId = ?', [$valueId])
            ->execute();

        return $qb->fetchBool();
    }

    public function deleteMetadataEnumValue(string $valueId) {

    }

    public function getMetadataEnumValueById(string $valueId) {
        return $this->getRow('custom_metadata_list_values', 'valueId', $valueId);
    }

    public function getMetadataEnumValueUsage(string $metadataId, string $key) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('documents_custom_metadata')
            ->where('metadataId = ?', [$metadataId])
            ->andWhere('value = ?', [$key])
            ->execute();

        return $qb->fetchAll();
    }

    public function getMetadataById(string $metadataId) {
        $qb = $this->composeQueryForMetadata();
        $qb->andWhere('metadataId = ?', [$metadataId])
            ->execute();

        return $qb->fetch();
    }

    public function getMetadataByTitle(string $title) {
        $qb = $this->composeQueryForMetadata();
        $qb->andWhere('title = ?', [$title])
            ->execute();

        return $qb->fetchAll();
    }
}

?>