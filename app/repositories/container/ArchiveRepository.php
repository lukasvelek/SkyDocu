<?php

namespace App\Repositories\Container;

use App\Constants\Container\ArchiveFolderStatus;
use App\Repositories\ARepository;
use QueryBuilder\QueryBuilder;

/**
 * ArchiveRepository is a repository used for manipulating archive data
 * 
 * @author Lukas Velek
 */
class ArchiveRepository extends ARepository {
    /**
     * Creates a new archive folder
     * 
     * @param string $folderId Folder ID
     * @param string $title Title
     * @param ?string $parentFolderId Parent Folder ID
     */
    public function insertNewArchiveFolder(string $folderId, string $title, ?string $parentFolderId = null): bool {
        $qb = $this->qb(__METHOD__);

        $keys = [
            'folderId',
            'title',
            'status'
        ];
        $values = [
            $folderId,
            $title,
            ArchiveFolderStatus::NEW
        ];

        if($parentFolderId !== null) {
            $keys[] = 'parentFolderId';
            $values[] = $parentFolderId;
        }

        $qb->insert('archive_folders', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Updates given archive folder
     * 
     * @param string $folderId Folder ID
     * @param array $data Data to write
     */
    public function updateArchiveFolder(string $folderId, array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->update('archive_folders')
            ->set($data)
            ->where('folderId = ?', [$folderId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Removes archive folder
     * 
     * @param string $folderId Folder ID
     */
    public function removeArchiveFolder(string $folderId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('archive_folders')
            ->where('folderId = ?', [$folderId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Inserts given document to the given archive folder
     * 
     * @param string $relationId Document to archive relation ID
     * @param string $documentId Document ID
     * @param string $folderId Folder ID
     */
    public function insertDocumentToArchiveFolder(string $relationId, string $documentId, string $folderId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('archive_folder_document_relation', ['relationId', 'documentId', 'folderId'])
            ->values([$relationId, $documentId, $folderId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Deletes a relation between document and archive folder
     * 
     * @param string $documentId Document ID
     * @param string $folderId Folder ID
     */
    public function removeDocumentFromArchiveFolder(string $documentId, string $folderId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('archive_folder_document_relation')
            ->where('documentId = ?', [$documentId])
            ->andWhere('folderId = ?', [$folderId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Returns an instance of QueryBuilder with select to archive folders
     */
    public function composeQueryForArchiveFolders(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('archive_folders');

        return $qb;
    }

    /**
     * Returns an instance of QueryBuilder with select for document in given archive folder
     */
    public function composeQueryForDocumentsInArchiveFolder(string $folderId): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('archive_folder_document_relation')
            ->where('folderId = ?', [$folderId]);

        return $qb;
    }
}

?>