<?php

namespace App\Repositories\Container;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\ARepository;

class DocumentRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function composeQueryForDocuments() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('documents')
            ->orderBy('dateModified', 'DESC');

        return $qb;
    }

    public function getSharedDocumentsForUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['documentId'])
            ->from('document_sharing')
            ->where('userId = ?', [$userId])
            ->andWhere('dateValidUntil > current_timestamp()')
            ->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[] = $row['documentId'];
        }

        return $documents;
    }

    public function getCustomMetadataForDocument(string $documentId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('documents_custom_metadata')
            ->where('documentId = ?', [$documentId])
            ->execute();

        $data = [];
        while($row = $qb->fetchAssoc()) {
            $data[$row['metadataId']] = $row['value'];
        }

        return $data;
    }

    public function getMetadataValues(string $metadataId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('custom_metadata_list_values')
            ->where('metadataId = ?', [$metadataId])
            ->execute();

        $values = [];
        while($row = $qb->fetchAssoc()) {
            $values[$row['metadataKey']] = $row['title'];
        }

        return $values;
    }

    public function composeQueryForDocumentCustomMetadataValues() {
        $qb = $this->qb(__METHOD__);
        
        $qb->select(['*'])
            ->from('documents_custom_metadata');

        return $qb;
    }

    public function createNewDocument(string $documentId, array $metadataValues) {
        $qb = $this->qb(__METHOD__);

        $keys = [];
        $values = [];
        foreach($metadataValues as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }

        $keys[] = 'documentId';
        $values[] = $documentId;

        $qb->insert('documents', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function createNewCustomMetadataEntry(string $entryId, array $data) {
        $qb = $this->qb(__METHOD__);

        $keys = [];
        $values = [];
        foreach($data as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }

        $keys[] = 'entryId';
        $values[] = $entryId;

        $qb->insert('documents_custom_metadata', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function getDocumentById(string $documentId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('documents')
            ->where('documentId = ?', [$documentId])
            ->execute();

        return $qb->fetch();
    }

    public function updateDocument(string $documentId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('documents')
            ->set($data)
            ->where('documentId = ?', [$documentId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>