<?php

namespace App\Managers\Container;

use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\FileStorageRepository;

/**
 * FileStorageManager contains high-level API operations for stored files manipulation
 * 
 * @author Lukas Velek
 */
class FileStorageManager extends AManager {
    private FileStorageRepository $fileStorageRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, FileStorageRepository $fileStorageRepository) {
        parent::__construct($logger, $entityManager);

        $this->fileStorageRepository = $fileStorageRepository;
    }

    /**
     * Checks if given document has a file
     * 
     * @param string $documentId Document ID
     */
    public function doesDocumentHaveFile(string $documentId): bool {
        try {
            $this->getFileForDocumentId($documentId);
            return true;
        } catch(AException $e) {
            return false;
        }
    }

    /**
     * Returns an instance of DatabaseRow with file for given document
     * 
     * @param string $documentId Document ID
     */
    public function getFileForDocumentId(string $documentId): DatabaseRow {
        $row = $this->fileStorageRepository->getFileForDocumentId($documentId);

        if($row === null) {
            throw new NonExistingEntityException('File does not exist.', null, false);
        }

        return DatabaseRow::createFromDbRow($row);
    }

    /**
     * Creates a new file
     * 
     * @param string $documentId Document ID
     * @param string $userId User ID
     * @param string $filename Filename
     * @param string $filepath Filepath
     * @param int $filesize Filesize
     * @return string File ID
     */
    public function createNewFile(string $documentId, string $userId, string $filename, string $filepath, int $filesize): string {
        $filepath = str_replace('\\', '\\\\', $filepath);

        $fileId = $this->createId(EntityManager::C_FILE_STORAGE);

        if(!$this->fileStorageRepository->createNewStoredFile($fileId, $filename, $filepath, $filesize, $userId)) {
            throw new GeneralException('Database error.');
        }

        $relationId = $this->createId(EntityManager::C_DOCUMENT_FILE_RELATION);

        if(!$this->fileStorageRepository->createNewFileDocumentRelation($relationId, $documentId, $fileId)) {
            throw new GeneralException('Database error.');
        }

        return $fileId;
    }
}

?>