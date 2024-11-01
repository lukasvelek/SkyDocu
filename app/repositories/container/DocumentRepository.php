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
}

?>