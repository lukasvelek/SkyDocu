<?php

namespace App\Managers\Container;

use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ArchiveRepository;

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

    public function getArchiveFolderById(string $folderId) {
        $folder = $this->archiveRepository->getFolderById($folderId);

        if($folder === null) {
            throw new NonExistingEntityException('Archive folder does not exist.');
        }

        return DatabaseRow::createFromDbRow($folder);
    }
}

?>