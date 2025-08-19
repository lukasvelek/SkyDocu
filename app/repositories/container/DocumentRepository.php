<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;
use PeeQL\Operations\QueryOperation;
use PeeQL\Result\QueryResult;

class DocumentRepository extends ARepository {
    public function composeQueryForDocuments() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('documents')
            ->orderBy('dateModified', 'DESC');

        return $qb;
    }

    public function composeQueryForSharedDocuments() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('document_sharing')
            ->orderBy('dateCreated', 'DESC');

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

    public function getSharedDocumentsByUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['documentId'])
            ->from('document_sharing')
            ->where('authorUserId = ?', [$userId])
            ->andWhere('dateValidUntil > current_timestamp()')
            ->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[] = $row['documentId'];
        }

        return $documents;
    }

    public function getSharesForDocumentIdsByUserId(array $documentIds, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('document_sharing')
            ->where('authorUserId = ?', [$userId])
            ->andWhere('dateValidUntil > current_timestamp()')
            ->andWhere($qb->getColumnInValues('documentId', $documentIds))
            ->execute();

        $rows = [];
        while($row = $qb->fetchAssoc()) {
            $rows[] = $row;
        }

        return $rows;
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
            ->orderBy('metadataKey')
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

    public function getDocumentsByIds(array $documentIds) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('documents')
            ->where($qb->getColumnInValues('documentId', $documentIds))
            ->execute();

        return $qb->fetchAll();
    }

    public function updateDocument(string $documentId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('documents')
            ->set($data)
            ->where('documentId = ?', [$documentId])
            ->execute();

        return $qb->fetchBool();
    }

    public function bulkUpdateDocuments(array $documentIds, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('documents')
            ->set($data)
            ->where($qb->getColumnInValues('documentId', $documentIds))
            ->execute();

        return $qb->fetchBool();
    }

    public function createNewDocumentSharing(string $sharingId, string $documentId, string $sharedByUserId, string $sharedToUserId, string $dateValidUntil) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('document_sharing', ['sharingId', 'documentId', 'authorUserId', 'userId', 'dateValidUntil'])
            ->values([$sharingId, $documentId, $sharedByUserId, $sharedToUserId, $dateValidUntil])
            ->execute();

        return $qb->fetchBool();
    }

    public function getFileIdForDocumentId(string $documentId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['fileId'])
            ->from('document_file_relation')
            ->where('documentId = ?', [$documentId])
            ->execute();

        return $qb->fetch('fileId');
    }

    public function composeQueryForDocumentFileRelations() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('document_file_relation');

        return $qb;
    }

    public function deleteDocumentFileRelation(string $documentId, string $fileId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('document_file_relation')
            ->where('documentId = ?', [$documentId])
            ->andWhere('fileId = ?', [$fileId])
            ->execute();

        return $qb->fetchBool();
    }

    public function get(QueryOperation $operation): QueryResult {
        return $this->processPeeQL('documents', $operation);
    }
}

?>