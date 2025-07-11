<?php

namespace App\Components\Widgets\FileStorageStatsWidget;

use App\Components\Widgets\Widget;
use App\Core\DB\DatabaseManager;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\UnitConversionHelper;
use App\Logger\Logger;
use App\Managers\ContainerManager;
use App\Repositories\Container\FileStorageRepository;

/**
 * FileStorageStatsWidget display statistics for application file storage
 * 
 * @author Lukas Velek
 */
class FileStorageStatsWidget extends Widget {
    private ContainerManager $containerManager;
    private DatabaseManager $dbManager;
    private Logger $logger;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request
     * @param ContainerManager $containerManager
     * @param DatabaseManager $dbManageer
     * @param Logger $logger
     */
    public function __construct(
        HttpRequest $request,
        ContainerManager $containerManager,
        DatabaseManager $dbManager,
        Logger $logger
    ) {
        parent::__construct($request);

        $this->containerManager = $containerManager;
        $this->dbManager = $dbManager;
        $this->logger = $logger;
    }

    public function startup() {
        parent::startup();

        $this->setData($this->processData());
        $this->setTitle('File storage statistics');
        $this->enableRefresh();
    }

    /**
     * Processes widget data
     */
    private function processData(): array {
        $data = $this->fetchDataFromDb();

        $rows = [
            'All files' => $data['totalRows'],
            'Total file size' => $data['totalSize'],
            'Average file count for containers' => $data['avgFileCountForContainers']
        ];

        return $rows;
    }

    /**
     * Fetches data from database
     */
    private function fetchDataFromDb(): array {
        $totalFiles = 0.0;
        $totalFileSize = 0.0;
        $avgFileCountForContainers = 0.0;
        
        try {
            $totalFiles = $this->fetchTotalFileCountFromDb();
            $totalFileSize = $this->fetchTotalFileSizeFromDb();
            $avgFileCountForContainers = $this->fetchAverageFileCountForContainersFromDb();
        } catch(AException $e) {
            
        }

        return [
            'totalRows' => $totalFiles,
            'totalSize' => $totalFileSize,
            'avgFileCountForContainers' => $avgFileCountForContainers
        ];
    }

    /**
     * Fetches average file count for container from database
     */
    private function fetchAverageFileCountForContainersFromDb(): int {
        $containers = $this->getAllContainers();

        $storedFiles = 0;
        foreach($containers as $containerId => $container) {
            try {
                $conn = $this->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());
            } catch(AException $e) {
                throw new GeneralException('Could not establish connection to database for container ID \'' . $containerId . '\'.', $e);
            }

            $fileStorageRepository = new FileStorageRepository($conn, $this->logger, $this->app->transactionLogRepository);
            $fileStorageRepository->setContainerId($containerId);

            $qb = $fileStorageRepository->composeQueryForStoredFiles();
            $qb->select(['COUNT(*) AS cnt'])
                ->regenerateSQL()
                ->execute();

            while($row = $qb->fetchAssoc()) {
                $storedFiles += (int)($row['cnt']);
            }
        }

        if(count($containers) == 0) {
            return 0;
        } else {
            return ceil($storedFiles / count($containers));
        }
    }

    /**
     * Fetches total file count from database
     */
    private function fetchTotalFileCountFromDb(): int {
        $containers = $this->getAllContainers();

        $storedFiles = 0;
        foreach($containers as $containerId => $container) {
            try {
                $conn = $this->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());
            } catch(AException $e) {
                throw new GeneralException('Could not establish connection to database for container ID \'' . $containerId . '\'.', $e);
            }

            $fileStorageRepository = new FileStorageRepository($conn, $this->logger, $this->app->transactionLogRepository);
            $fileStorageRepository->setContainerId($containerId);

            $qb = $fileStorageRepository->composeQueryForStoredFiles();
            $qb->select(['COUNT(*) AS cnt'])
                ->regenerateSQL()
                ->execute();

            while($row = $qb->fetchAssoc()) {
                $storedFiles += (int)($row['cnt']);
            }
        }

        return $storedFiles;
    }

    /**
     * Fetches total file size from database
     */
    private function fetchTotalFileSizeFromDb(): string {
        $containers = $this->getAllContainers();

        $filesize = 0;
        foreach($containers as $containerId => $container) {
            try {
                $conn = $this->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());
            } catch(AException $e) {
                throw new GeneralException('Could not establish connection to database for container ID \'' . $containerId . '\'.', $e);
            }

            $fileStorageRepository = new FileStorageRepository($conn, $this->logger, $this->app->transactionLogRepository);
            $fileStorageRepository->setContainerId($containerId);

            $qb = $fileStorageRepository->composeQueryForStoredFiles();
            $qb->execute();

            while($row = $qb->fetchAssoc()) {
                $filesize += (int)($row['filesize']);
            }
        }

        return UnitConversionHelper::convertBytesToUserFriendly($filesize);
    }

    /**
     * Returns an array with all containers
     * 
     * @return array<string, \App\Entities\ContainerEntity>
     */
    private function getAllContainers(): array {
        $_containers = $this->containerManager->getAllContainers(true, true);

        $containers = [];
        foreach($_containers as $container) {
            /**
             * @var \App\Entities\ContainerEntity $container
             */
            $containers[$container->getId()] = $container;
        }

        return $containers;
    }
}

?>