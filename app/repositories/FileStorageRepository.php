<?php

namespace App\Repositories;

use QueryBuilder\QueryBuilder;

/**
 * FileStorageRepository contains low-level API methods for file storage
 * 
 * @author Lukas Velek
 */
class FileStorageRepository extends ARepository {
    /**
     * Composes a QueryBuilder instance for files in storage
     * 
     * @param ?string $containerId Container ID
     */
    public function composeQueryForFilesInStorage(?string $containerId = null): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('file_storage');

        if($containerId !== null) {
            $qb->where('containerId = ?', [$containerId]);
        }

        return $qb;
    }

    /**
     * Inserts a new file to storage
     * 
     * @param array $data Data array
     */
    public function createNewStoredFile(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('file_storage', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Returns file entry for file ID
     * 
     * @param string $fileId File ID
     * @param ?string $containerId Container ID
     */
    public function getFileById(string $fileId, ?string $containerId = null): mixed {
        $qb = $this->composeQueryForFilesInStorage($containerId);

        $qb->andWhere('fileId = ?', [$fileId])
            ->execute();

        return $qb->fetch();
    }

    /**
     * Returns file entry for file hash
     * 
     * @param string $hash File hash
     * @param ?string $containerId Container ID
     */
    public function getFileByHash(string $hash, ?string $containerId = null): mixed {
        $qb = $this->composeQueryForFilesInStorage($containerId);

        $qb->andWhere('hash = ?', [$hash])
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