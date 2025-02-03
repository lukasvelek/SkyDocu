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
     * Inserts a new stored file
     * 
     * @param string $fileId File ID
     * @param string $filename File name
     * @param string $filepath File path
     * @param int $filesize File size
     * @param string $userId User ID
     */
    public function createNewStoredFile(string $fileId, string $filename, string $filepath, int $filesize, string $userId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('file_storage', ['fileId', 'filename', 'filepath', 'filesize', 'userId'])
            ->values([$fileId, $filename, $filepath, $filesize, $userId])
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
}

?>