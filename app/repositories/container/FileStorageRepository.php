<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;
use QueryBuilder\QueryBuilder;

/**
 * FileStorageRepository contains low-level API operations for stored files manipulation
 * 
 * @author Lukas Velek
 */
class FileStorageRepository extends ARepository {
    /**
     * Composes a QueryBuilder isntance for document file relations
     */
    public function composeQueryForFileDocumentRelations(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('document_file_relation');

        return $qb;
    }

    /**
     * Inserts a new document-stored file relation
     * 
     * @param string $relationId Relation ID
     * @param string $documentId Document ID
     * @param string $fileId File ID
     */
    public function createNewFileDocumentRelation(string $relationId, string $documentId, string $fileId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('document_file_relation', ['relationId', 'documentId', 'fileId'])
            ->values([$relationId, $documentId, $fileId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Inserts a new process file relation
     * 
     * @param string $relationId Relation ID
     * @param string $instanceId Instance ID
     * @param string $fileId File ID
     */
    public function createNewFileProcessInstanceRelation(string $relationId, string $instanceId, string $fileId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('process_file_relation', ['relationId', 'instanceId', 'fileId'])
            ->values([$relationId, $instanceId, $fileId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Returns a file for given document
     * 
     * @param string $documentId Document ID
     */
    public function getFileForDocumentId(string $documentId): mixed {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('document_file_relation')
            ->where('documentId = ?', [$documentId])
            ->execute();

        return $qb->fetch();
    }

    /**
     * Deletes a document-file relation
     * 
     * @param string $documentId Document ID
     * @param string $fileId File ID
     */
    public function deleteDocumentFileRelation(string $documentId, string $fileId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('document_file_relation')
            ->where('documentId = ?', [$documentId])
            ->andWhere('fileId = ?', [$fileId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>