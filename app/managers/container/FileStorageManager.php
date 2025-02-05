<?php

namespace App\Managers\Container;

use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\Router;
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
            $this->getFileRelationForDocumentId($documentId);
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
    public function getFileRelationForDocumentId(string $documentId): DatabaseRow {
        $exp = new DateTime();
        $exp->modify('+1h');
        $cache = $this->cacheFactory->getCache(CacheNames::DOCUMENT_FILE_MAPPING, $exp);

        $row = $cache->load($documentId, function() use ($documentId) {
            return $this->fileStorageRepository->getFileForDocumentId($documentId);
        });

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

        $hash = $this->createUniqueHashForDb(256, EntityManager::C_FILE_STORAGE, 'hash');

        if(!$this->fileStorageRepository->createNewStoredFile($fileId, $filename, $filepath, $filesize, $userId, $hash)) {
            throw new GeneralException('Database error.');
        }

        $relationId = $this->createId(EntityManager::C_DOCUMENT_FILE_RELATION);

        if(!$this->fileStorageRepository->createNewFileDocumentRelation($relationId, $documentId, $fileId)) {
            throw new GeneralException('Database error.');
        }

        return $fileId;
    }

    /**
     * Returns a file
     * 
     * @param string $fileId File ID
     */
    public function getFileById(string $fileId): DatabaseRow {
        $exp = new DateTime();
        $exp->modify('+1h');
        $cache = $this->cacheFactory->getCache(CacheNames::FILES);

        $file = $cache->load($fileId, function() use ($fileId) {
            return $this->fileStorageRepository->getFileById($fileId);
        });

        if($file === null) {
            throw new NonExistingEntityException('File does not exist.');
        }

        return DatabaseRow::createFromDbRow($file);
    }

    /**
     * Returns a file
     * 
     * @param string $hash File hash
     */
    public function getFileByHash(string $hash): DatabaseRow {
        $exp = new DateTime();
        $exp->modify('+1h');
        $cache = $this->cacheFactory->getCache(CacheNames::FILE_HASH_TO_ID_MAPPING);

        $fileId = $cache->load($hash, function() use ($hash) {
            $file = $this->fileStorageRepository->getFileByHash($hash);

            if($file !== null) {
                return $file['fileId'];
            }

            return null;
        });

        if($fileId === null) {
            throw new NonExistingEntityException('File does not exist.');
        }

        return $this->getFileById($fileId);
    }

    /**
     * Generates download link for file in document by given document ID
     * 
     * @param string $documentId Document ID
     */
    public function generateDownloadLinkForFileInDocumentByDocumentId(string $documentId): string {
        $fileRelation = $this->getFileRelationForDocumentId($documentId);
        $file = $this->getFileById($fileRelation->fileId);

        return Router::generateUrl([
            'page' => 'User:FileStorage',
            'action' => 'download',
            'hash' => $file->hash
        ]);
    }
}

?>