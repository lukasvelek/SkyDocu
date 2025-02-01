<?php

namespace App\Managers\Container;

use App\Constants\Container\ArchiveFolderStatus;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ArchiveRepository;
use QueryBuilder\QueryBuilder;

/**
 * ArchiveManager is used for high-level archive manipulation
 * 
 * @author Lukas Velek
 */
class ArchiveManager extends AManager {
    private ArchiveRepository $archiveRepository;

    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        ArchiveRepository $archiveRepository
    ) {
        parent::__construct($logger, $entityManager);

        $this->archiveRepository = $archiveRepository;
    }

    /**
     * Creates a new archive folder
     * 
     * @param string $title Folder title
     * @param ?string $parentFolderId Parent Folder ID
     */
    public function createNewArchiveFolder(string $title, ?string $parentFolderId = null) {
        $folderId = $this->createId(EntityManager::C_ARCHIVE_FOLDERS);

        if(!$this->archiveRepository->insertNewArchiveFolder($folderId, $title, $parentFolderId)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Returns all archive folders until root is hit
     * 
     * @param string $folderId Beginning folder ID
     */
    public function getArchiveFolderPathToRoot(string $folderId): array {
        $folders = [];

        $run = true;

        $folder = $this->getArchiveFolderById($folderId);

        $folderIds = [];
        $folderObjs = [];
        do {
            if($folder->parentFolderId === null) {
                $run = false;
            }
            array_unshift($folderIds, $folder->folderId);
            $folderObjs[$folder->folderId] = $folder;

            if($folder->parentFolderId !== null) {
                $folder = $this->getArchiveFolderById($folder->parentFolderId);
            }
        } while($run === true);

        foreach($folderIds as $folderId) {
            $folders[] = $folderObjs[$folderId];
        }

        return $folders;
    }

    /**
     * Returns an instance of DatabaseRow for given archive folder ID
     * 
     * @param string $folderId Folder ID
     */
    public function getArchiveFolderById(string $folderId): DatabaseRow {
        $folder = $this->archiveRepository->getFolderById($folderId);

        if($folder === null) {
            throw new NonExistingEntityException('Archive folder does not exist.');
        }

        return DatabaseRow::createFromDbRow($folder);
    }

    /**
     * Returns an array of subfolders for given archive folder
     * 
     * @param string $folderId Folder ID
     */
    public function getSubfoldersForArchiveFolder(string $folderId): array {
        $qb = $this->archiveRepository->composeQueryForArchiveFolders();
        $qb->andWhere('parentFolderId = ?', [$folderId])
            ->execute();

        $subfolders = [];
        while($row = $qb->fetchAssoc()) {
            $row = DatabaseRow::createFromDbRow($row);

            $subfolders[] = $row;
        }

        return $subfolders;
    }

    /**
     * Returns an array of documents for given archive folder
     * 
     * @param string $folderId Folder ID
     */
    public function getDocumentsForArchiveFolder(string $folderId): array {
        $qb = $this->archiveRepository->composeQueryForDocumentsInArchiveFolder($folderId);
        $qb->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[] = $row['documentId'];
        }

        return $documents;
    }

    /**
     * Checks a status for all folders
     * 
     * @param string $folderId Folder ID
     * @param int $desiredStatus Desired status
     */
    public function checkStatusForSubfolders(string $folderId, int $desiredStatus): bool {
        $folderPath = $this->getArchiveFolderPathToRoot($folderId);

        $ok = true;
        foreach($folderPath as $folder) {
            if($folder->status != $desiredStatus) {
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * Composes QueryBuilder for select for all available archive folders
     */
    public function composeQueryForAvailableArchiveFolders(): QueryBuilder {
        $qb = $this->archiveRepository->composeQueryForArchiveFolders();
        $qb->andWhere('status = ?', [ArchiveFolderStatus::NEW]);
        return $qb;
    }

    /**
     * Returns all available archive folders
     * 
     * @param bool $orderByTitle Order archive folders by title (a-z)
     */
    public function getAvailableArchiveFolders(bool $orderByTitle = true): array {
        $qb = $this->composeQueryForAvailableArchiveFolders();
        if($orderByTitle) {
            $qb->orderBy('title', 'DESC');
        }
        $qb->execute();

        $archiveFolders = [];
        while($row = $qb->fetchAssoc()) {
            $row = DatabaseRow::createFromDbRow($row);
            $archiveFolders[] = $row;
        }

        return $archiveFolders;
    }

    /**
     * Inserts document to archive folder
     * 
     * @param string $documentId Document ID
     * @param string $folderId Folder ID
     */
    public function insertDocumentToArchiveFolder(string $documentId, string $folderId) {
        $relationId = $this->createId(EntityManager::C_ARCHIVE_FOLDER_DOCUMENT_RELATION);

        if(!$this->archiveRepository->insertDocumentToArchiveFolder($relationId, $documentId, $folderId)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Removes given document from an archive folder
     * 
     * @param string $documentId Document ID
     */
    public function removeDocumentFromArchiveFolder(string $documentId) {
        $folderId = $this->getArchiveFolderForDocument($documentId);

        if(!$this->archiveRepository->removeDocumentFromArchiveFolder($documentId, $folderId)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Returns an instance of DatabaseRow of the default archive folder
     */
    public function getDefaultFolder(): DatabaseRow {
        $qb = $this->archiveRepository->composeQueryForArchiveFolders();
        $folder = $qb->andWhere('isSystem = 1')
            ->andWhere('title = ?', ['Default'])
            ->execute()
            ->fetch();

        if($folder === null) {
            throw new NonExistingEntityException('Default folder does not exist.');
        }

        return DatabaseRow::createFromDbRow($folder);
    }

    /**
     * Checks if given document is in an archive folder
     * 
     * @param string $documentId Document ID
     */
    public function isDocumentInArchiveFolder(string $documentId): bool {
        $folder = $this->archiveRepository->getArchiveFolderForDocument($documentId);

        return $folder !== null;
    }

    /**
     * Returns an instance of DatabaseRow of an archive folder for given document
     * 
     * @param string $documentId Document ID
     */
    public function getArchiveFolderForDocument(string $documentId): string {
        $folderId = $this->archiveRepository->getArchiveFolderForDocument($documentId);

        return $folderId;
    }
}

?>