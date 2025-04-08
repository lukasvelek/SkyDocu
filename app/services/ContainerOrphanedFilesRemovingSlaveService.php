<?php

namespace App\Services;

use App\Constants\Container\DocumentStatus;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseManager;
use App\Core\FileManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\ServiceException;
use App\Logger\Logger;
use App\Managers\Container\FileStorageManager;
use App\Managers\ContainerManager;
use App\Managers\EntityManager;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\Container\FileStorageRepository;
use App\Repositories\ContentRepository;
use Exception;

class ContainerOrphanedFilesRemovingSlaveService extends AService {
    private string $containerId;

    private ContainerManager $containerManager;
    private DatabaseManager $dbManager;

    public function __construct(Logger $logger, ServiceManager $serviceManager, ContainerManager $containerManager, DatabaseManager $dbManager) {
        parent::__construct('ContainerOrphanedFilesRemovingSlave', $logger, $serviceManager);

        $this->containerManager = $containerManager;
        $this->dbManager = $dbManager;
    }

    public function run() {
        global $argv;
        $_argv = $argv;
        unset($_argv[0]);

        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop(null, $_argv);
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop($e, $_argv);
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        global $argv;

        if(count($argv) == 1) {
            throw new ServiceException('No arguments passed.');
        }

        $this->containerId = $argv[1];

        $container = $this->containerManager->getContainerById($this->containerId);

        try {
            $containerConn = $this->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());
        } catch(AException|Exception $e) {
            $this->logError(sprintf('Could not connect to database for container \'%s\'.', $this->containerId));
        }

        $contentRepository = new ContentRepository($containerConn, $this->logger);
        $entityManager = new EntityManager($this->logger, $contentRepository);
        $fileStorageRepository = new FileStorageRepository($containerConn, $this->logger);
        $fileStorageManager = new FileStorageManager($this->logger, $entityManager, $fileStorageRepository);
        $documentRepository = new DocumentRepository($containerConn, $this->logger);

        $this->processFiles($fileStorageManager, $documentRepository);
    }

    private function processFiles(FileStorageManager $fileStorageManager, DocumentRepository $documentRepository) {
        
        /**
         * 1. get all files
         * 2. get all document-file relations
         * 3. get all documents from document-file relations
         * 4. delete those files that have a document-file relation and the document does not exist or is shredded
         * 5. delete those files that don't have a document-file relation and are at least 30 days old
         */

        $fileIdsToDelete = [];

        // get all files
        $qb = $fileStorageManager->fileStorageRepository->composeQueryForStoredFiles();
        $qb->execute();

        $fileIds = [];
        while($row = $qb->fetchAssoc()) {
            $fileIds[] = $row['fileId'];
        }

        $this->logInfo(sprintf('Found %d files stored for container.', count($fileIds)));

        // get all document-file relations
        $qb = $fileStorageManager->fileStorageRepository->composeQueryForFileDocumentRelations();
        $qb->execute();

        $documentIds = [];
        $documentToFileMapping = [];
        while($row = $qb->fetchAssoc()) {
            $documentIds[] = $row['documentId'];
            $documentToFileMapping[$row['documentId']] = $row['fileId'];
        }

        $this->logInfo(sprintf('Found %d document-file relations for container.', count($documentIds)));

        // get all documents
        $qb = $documentRepository->composeQueryForDocuments();
        $qb->andWhere($qb->getColumnInValues('documentId', $documentIds))
            ->regenerateSQL()
            ->execute();

        $documentsToDelete = [];
        while($row = $qb->fetchAssoc()) {
            if(!in_array($row['status'], [DocumentStatus::DELETED, DocumentStatus::SHREDDED])) {
                continue;
            }

            $documentsToDelete[] = $row['documentId'];
        }

        $this->logInfo(sprintf('Found %d documents that are shredded or deleted and contain a file for container.', count($documentsToDelete)));

        // delete those files that have a document-file relation and the document does not exist or is shredded
        foreach($documentsToDelete as $documentId) {
            $fileIdsToDelete[] = $documentToFileMapping[$documentId];
        }

        // delete those files that don't have a document-file relation and are at least 30 days old
        $fileToFilePathMapping = [];

        foreach($fileIds as $fileId) {
            $this->logInfo(sprintf('Processing file \'%s\'.', $fileId));
            if(in_array($fileId, $documentToFileMapping)) {
                continue;
            }

            $file = $fileStorageManager->fileStorageRepository->getFileById($fileId);
            
            $fileToFilePathMapping[$fileId] = $file['filepath'];

            $date = new DateTime(strtotime($file['dateCreated']));
            $date->modify('+30d');
            $date = strtotime($date->getResult());

            if($date < time()) {
                $this->logInfo(sprintf('File \'%s\' is older than maximum timestamp of creation.', $fileId));
                $fileIdsToDelete[] = $fileId;
            }
        }

        // delete files
        $this->logInfo(sprintf('Found total of %d files to delete.', count($fileIdsToDelete)));

        foreach($fileIdsToDelete as $fileId) {
            try {
                $fileStorageManager->fileStorageRepository->beginTransaction(__METHOD__);

                $documentId = array_search($fileId, $documentToFileMapping);

                if(!$fileStorageManager->fileStorageRepository->deleteDocumentFileRelation($documentId, $fileId)) {
                    throw new GeneralException('Database error 1.');
                }

                if(!$fileStorageManager->fileStorageRepository->deleteStoredFile($fileId)) {
                    throw new GeneralException('Database error 2.');
                }

                $filepath = $fileToFilePathMapping[$fileId];

                $this->logInfo(sprintf('Deleting file \'%s\'.', $filepath));
                if(!FileManager::deleteFile($filepath)) {
                    throw new GeneralException('File error.');
                }

                $fileStorageManager->fileStorageRepository->commit($this->serviceManager->getServiceUserId(), __METHOD__);

                $this->logInfo(sprintf('File \'%s\' deleted.', $fileId));
            } catch(AException $e) {
                $fileStorageManager->fileStorageRepository->rollback(__METHOD__);

                $this->logInfo(sprintf('File \'%s\' could not be deleted. Reason: ' . $e->getMessage(), $fileId));
            }
        }
    }
}

?>