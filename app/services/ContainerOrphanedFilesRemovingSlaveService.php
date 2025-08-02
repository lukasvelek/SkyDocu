<?php

namespace App\Services;

use App\Constants\Container\DocumentStatus;
use App\Core\Application;
use App\Core\Container;
use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\ServiceException;
use Exception;

class ContainerOrphanedFilesRemovingSlaveService extends AService {
    private string $containerId;

    public function __construct(Application $app) {
        parent::__construct('ContainerOrphanedFilesRemovingSlave', $app);
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

        $container = $this->getContainerInstance($this->containerId);

        $this->processFiles($container);
    }

    private function processFiles(Container $container) {
        
        /**
         * 1. get all files
         * 2. get all document-file relations
         * 3. get all documents from document-file relations
         * 4. delete those files that have a document-file relation and the document does not exist or is shredded
         * 5. delete those files that don't have a document-file relation and are at least 30 days old
         */

        $fileIdsToDelete = [];

        // get all files
        $qb = $this->app->fileStorageRepository->composeQueryForFilesInStorage($container->containerId);
        $qb->execute();

        $fileIds = [];
        while($row = $qb->fetchAssoc()) {
            $fileIds[] = $row['fileId'];
        }

        $this->logInfo(sprintf('Found %d files stored for container.', count($fileIds)));

        // get all document-file relations
        $qb = $container->fileStorageManager->fileStorageRepository->composeQueryForFileDocumentRelations();
        $qb->execute();

        $documentIds = [];
        $documentToFileMapping = [];
        while($row = $qb->fetchAssoc()) {
            $documentIds[] = $row['documentId'];
            $documentToFileMapping[$row['documentId']] = $row['fileId'];
        }

        $this->logInfo(sprintf('Found %d document-file relations for container.', count($documentIds)));

        // get all documents
        $qb = $container->documentRepository->composeQueryForDocuments();
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

            $file = $this->app->fileStorageManager->getFileById($fileId, $this->containerId);
            
            $fileToFilePathMapping[$fileId] = $file->filepath;

            $date = new DateTime(strtotime($file->dateCreated));
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
                $container->fileStorageManager->fileStorageRepository->beginTransaction(__METHOD__);

                $documentId = array_search($fileId, $documentToFileMapping);

                if(!$container->fileStorageManager->fileStorageRepository->deleteDocumentFileRelation($documentId, $fileId)) {
                    throw new GeneralException('Database error 1.');
                }

                try {
                    $this->app->fileStorageManager->deleteFile($fileId);
                } catch(AException $e) {
                    throw new GeneralException('Database error 2.', $e);
                }

                $filepath = $fileToFilePathMapping[$fileId];

                $this->logInfo(sprintf('Deleting file \'%s\'.', $filepath));
                if(!FileManager::deleteFile($filepath)) {
                    throw new GeneralException('File error.');
                }

                $container->fileStorageManager->fileStorageRepository->commit($this->serviceManager->getServiceUserId(), __METHOD__);

                $this->logInfo(sprintf('File \'%s\' deleted.', $fileId));
            } catch(AException $e) {
                $container->fileStorageManager->fileStorageRepository->rollback(__METHOD__);

                $this->logInfo(sprintf('File \'%s\' could not be deleted. Reason: ' . $e->getMessage(), $fileId));
            }
        }
    }
}

?>