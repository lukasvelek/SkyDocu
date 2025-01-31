<?php

namespace App\Managers\Container;

use App\Exceptions\GeneralException;
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
}

?>