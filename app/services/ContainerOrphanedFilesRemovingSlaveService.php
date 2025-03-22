<?php

namespace App\Services;

use App\Core\DB\DatabaseManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
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
}

?>