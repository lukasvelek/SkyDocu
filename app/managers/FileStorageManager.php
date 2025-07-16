<?php

namespace App\Managers;

use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\FileStorageRepository;

/**
 * FileStorageManager contains high-level API methods for file storage
 * 
 * @author Lukas Velek
 */
class FileStorageManager extends AManager {
    private FileStorageRepository $fileStorageRepository;

    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        FileStorageRepository $fileStorageRepository
    ) {
        parent::__construct($logger, $entityManager);

        $this->fileStorageRepository = $fileStorageRepository;
    }

    /**
     * Creates a new file
     * 
     * @param string $userId User ID
     * @param string $filename Filename
     * @param string $filepath Filepath
     * @param int $filesize File size
     * @param ?string $containerId Container ID
     */
    public function createNewFile(string $userId, string $filename, string $filepath, int $filesize, ?string $containerId = null): string {
        $filepath = str_replace('\\', '\\\\', $filepath);

        $fileId = $this->createId(EntityManager::FILE_STORAGE);

        $hash = $this->createUniqueHashForDb(256, EntityManager::FILE_STORAGE, 'hash');

        $data = [
            'fileId' => $fileId,
            'userId' => $userId,
            'filename' => $filename,
            'filepath' => $filepath,
            'filesize' => $filesize,
            'hash' => $hash
        ];

        if($containerId !== null) {
            $data['containerId'] = $containerId;
        }

        if(!$this->fileStorageRepository->createNewStoredFile($data)) {
            throw new GeneralException('Database error.');
        }

        return $fileId;
    }

    /**
     * Returns file by file ID
     * 
     * @param string $fileId File ID
     * @param ?string $containerId Container ID
     */
    public function getFileById(string $fileId, ?string $containerId = null): DatabaseRow {
        $row = $this->fileStorageRepository->getFileById($fileId, $containerId);

        if($row === null) {
            throw new NonExistingEntityException('File does not exist.');
        }

        return DatabaseRow::createFromDbRow($row);
    }

    /**
     * Returns file by file hash
     * 
     * @param string $hash File hash
     * @param ?string $containerId Container ID
     */
    public function getFileByHash(string $hash, ?string $containerId = null): DatabaseRow {
        $row = $this->fileStorageRepository->getFileByHash($hash, $containerId);

        if($row === null) {
            throw new NonExistingEntityException('File does not exist.');
        }

        return DatabaseRow::createFromDbRow($row);
    }
}