<?php

namespace App\Managers\Container;

use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Repositories\Container\FileStorageRepository;

/**
 * FileStorageManager contains high-level API operations for stored files manipulation
 * 
 * @author Lukas Velek
 */
class FileStorageManager extends AManager {
    public FileStorageRepository $fileStorageRepository;

    public function __construct(Logger $logger, FileStorageRepository $fileStorageRepository) {
        parent::__construct($logger);

        $this->fileStorageRepository = $fileStorageRepository;
    }

    /**
     * Checks if given document has a file
     * 
     * @param string $documentId Document ID
     * @deprecated
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
     * Checks if documents in given document ID array have files and returns a document ID array of those with a file
     * 
     * @param array $documentIds Document ID array
     * @deprecated
     */
    public function doDocumentsHaveFile(array $documentIds): array {
        $documentsWithFile = [];
        foreach($documentIds as $documentId) {
            if($this->doesDocumentHaveFile($documentId)) {
                $documentsWithFile[] = $documentId;
            }
        }

        return $documentsWithFile;
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
     * Creates a new file-process instance relation
     * 
     * @param string $instanceId Process instance ID
     * @param string $fileId File ID
     * @throws GeneralException
     */
    public function createNewFileProcessInstanceRelation(string $instanceId, string $fileId) {
        $relationId = $this->createId();

        if(!$this->fileStorageRepository->createNewFileProcessInstanceRelation($relationId, $instanceId, $fileId)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Creates a new file-document relation
     * 
     * @param string $documentId Document ID
     * @param string $fileId File ID
     */
    public function createNewFileDocumentRelation(string $documentId, string $fileId) {
        $relationId = $this->createId();

        if(!$this->fileStorageRepository->createNewFileDocumentRelation($relationId, $documentId, $fileId)) {
            throw new GeneralException('Database error.');
        }
    }
}

?>