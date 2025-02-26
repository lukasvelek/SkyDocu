<?php

namespace App\Services;

use App\Core\DB\DatabaseManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Managers\Container\FileStorageManager;
use App\Managers\ContainerManager;
use App\Managers\EntityManager;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\Container\FileStorageRepository;
use App\Repositories\ContentRepository;
use Exception;

class ContainerOrphanedFilesRemovingService extends AService {
    private ContainerManager $containerManager;
    private DatabaseManager $databaseManager;

    public function __construct(
        Logger $logger,
        ServiceManager $serviceManager,
        ContainerManager $containerManager,
        DatabaseManager $databaseManager
    ) {
        parent::__construct('ContainerOrphanedFilesRemoving', $logger, $serviceManager);

        $this->containerManager = $containerManager;
        $this->databaseManager = $databaseManager;
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop(true);
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here

        $this->logInfo('Obtaining containers...');

        $containers = $this->getAllContainers();

        $this->logInfo(sprintf('Found %d containers.', count($containers)));

        foreach($containers as $containerId) {
            $this->logInfo(sprintf('Starting processing container \'%s\'.', $containerId));
            $container = $this->containerManager->getContainerById($containerId);
            $containerConnection = $this->databaseManager->getConnectionToDatabase($container->databaseName);

            $contentRepository = new ContentRepository($containerConnection, $this->logger);
            $entityManager = new EntityManager($this->logger, $contentRepository);
            $fileStorageRepository = new FileStorageRepository($containerConnection, $this->logger);
            $fileStorageManager = new FileStorageManager($this->logger, $entityManager, $fileStorageRepository);
            $documentRepository = new DocumentRepository($containerConnection, $this->logger);

            $this->processFiles($fileStorageManager, $documentRepository);
            $this->logInfo(sprintf('Container \'%s\' processed.', $containerId));
        }
    }

    private function processFiles(FileStorageManager $fileStorageManager, DocumentRepository $documentRepository) {
        // get all document file relations
        
        $qb = $fileStorageManager->fileStorageRepository->composeQueryForFileDocumentRelations();
        $qb->execute();
        
        $documentIds = [];
        $documentToFileMapping = [];
        while($row = $qb->fetchAssoc()) {
            $documentIds[] = $row['documentId'];
            $documentToFileMapping[$row['documentId']] = $row['fileId'];
        }
        
        $this->logInfo(sprintf('Found %d files stored for container.', count($documentToFileMapping)));
        
        // find documents by all document ids from previous step
        $qb = $documentRepository->composeQueryForDocuments();
        $qb->andWhere($qb->getColumnInValues('documentId', $documentIds))
            ->regenerateSQL()
            ->execute();

        $existing = [];
        while($row = $qb->fetchAssoc()) {
            $documentId = $row['documentId'];

            $existing[] = $documentId;
        }

        // remove not found files
        $filesToDelete = [];
        foreach($documentIds as $documentId) {
            if(!in_array($documentId, $existing)) {
                $fileId = $documentToFileMapping[$documentId];
                $filesToDelete[] = $fileId;
            }
        }

        $this->logInfo(sprintf('Found %d files that are orphaned and can be deleted.', count($filesToDelete)));
        
        foreach($filesToDelete as $fileId) {
            $documentId = array_search($fileId, $documentToFileMapping);
            $fileStorageManager->fileStorageRepository->deleteStoredFile($fileId);
            $fileStorageManager->fileStorageRepository->deleteDocumentFileRelation($documentId, $fileId);
        }
    }

    private function getAllContainers() {
        $qb = $this->containerManager->containerRepository->composeQueryForContainers();
        $qb->execute();

        $containerIds = [];
        while($row = $qb->fetchAssoc()) {
            $containerIds[] = $row['containerId'];
        }

        return $containerIds;
    }
}

?>