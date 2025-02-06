<?php

namespace App\Components\Widgets\FileStorageStatsWidget;

use App\Components\Widgets\Widget;
use App\Core\DB\DatabaseManager;
use App\Core\Http\HttpRequest;
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
        $totalFiles = $this->fetchTotalFileCountFromDb();
        $totalFileSize = $this->fetchTotalFileSizeFromDb();
        $avgFileCountForContainers = $this->fetchAverageFileCountForContainersFromDb();

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
            $conn = $this->dbManager->getConnectionToDatabase($container->databaseName);

            $fileStorageRepository = new FileStorageRepository($conn, $this->logger);

            $qb = $fileStorageRepository->composeQueryForStoredFiles();
            $qb->select(['COUNT(*) AS cnt'])
                ->regenerateSQL()
                ->execute();

            while($row = $qb->fetchAssoc()) {
                $storedFiles += (int)($row['cnt']);
            }
        }

        return ceil($storedFiles / count($containers));
    }

    /**
     * Fetches total file count from database
     */
    private function fetchTotalFileCountFromDb(): int {
        $containers = $this->getAllContainers();

        $storedFiles = 0;
        foreach($containers as $containerId => $container) {
            $conn = $this->dbManager->getConnectionToDatabase($container->databaseName);

            $fileStorageRepository = new FileStorageRepository($conn, $this->logger);

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
            $conn = $this->dbManager->getConnectionToDatabase($container->databaseName);

            $fileStorageRepository = new FileStorageRepository($conn, $this->logger);

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
     */
    private function getAllContainers(): array {
        $qb = $this->containerManager->containerRepository->composeQueryForContainers();
        $qb->execute();

        $containerIds = [];
        while($row = $qb->fetchAssoc()) {
            $containerIds[] = $row['containerId'];
        }

        $containers = [];
        foreach($containerIds as $containerId) {
            $container = $this->containerManager->getContainerById($containerId, true);
            $containers[$containerId] = $container;
        }

        return $containers;
    }
}

?>