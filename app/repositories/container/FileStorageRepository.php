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
     * Composes a QueryBuilder instance for stored files
     */
    public function composeQueryForStoredFiles(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('file_storage');

        return $qb;
    }

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
     * Inserts a new stored file
     * 
     * @param string $fileId File ID
     * @param string $filename File name
     * @param string $filepath File path
     * @param int $filesize File size
     * @param string $userId User ID
     */
    public function createNewStoredFile(string $fileId, string $filename, string $filepath, int $filesize, string $userId, string $hash): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('file_storage', ['fileId', 'filename', 'filepath', 'filesize', 'userId', 'hash'])
            ->values([$fileId, $filename, $filepath, $filesize, $userId, $hash])
            ->execute();

        return $qb->fetchBool();
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
     * Returns a file row
     * 
     * @param string $fileId File ID
     */
    public function getFileById(string $fileId): mixed {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('file_storage')
            ->where('fileId = ?', [$fileId])
            ->execute();

        return $qb->fetch();
    }

    /**
     * Returns a file row
     * 
     * @param string $hash File hash
     */
    public function getFileByHash(string $hash): mixed {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('file_storage')
            ->where('hash = ?', [$hash])
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

    /**
     * Deletes a stored file
     * 
     * @param string $fileId File ID
     */
    public function deleteStoredFile(string $fileId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('file_storage')
            ->where('fileId = ?', [$fileId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>