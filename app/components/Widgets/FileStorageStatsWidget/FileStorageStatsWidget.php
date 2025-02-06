<?php

namespace App\Components\Widgets\FileStorageStatsWidget;

use App\Components\Widgets\Widget;
use App\Core\DB\DatabaseManager;
use App\Core\Http\HttpRequest;
use App\Helpers\UnitConversionHelper;
use App\Logger\Logger;
use App\Managers\ContainerManager;
use App\Repositories\Container\FileStorageRepository;

class FileStorageStatsWidget extends Widget {
    private ContainerManager $containerManager;
    private DatabaseManager $dbManager;
    private Logger $logger;

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

    private function processData() {
        $data = $this->fetchDataFromDb();

        $rows = [
            'All files' => $data['totalRows'],
            'Total file size' => $data['totalSize'],
            'Average file count for containers' => $data['avgFileCountForContainers']
        ];

        return $rows;
    }

    private function fetchDataFromDb() {
        $totalFiles = $this->fetchTotalFileCountFromDb();
        $totalFileSize = $this->fetchTotalFileSizeFromDb();
        $avgFileCountForContainers = $this->fetchAverageFileCountForContainersFromDb();

        return [
            'totalRows' => $totalFiles,
            'totalSize' => $totalFileSize,
            'avgFileCountForContainers' => $avgFileCountForContainers
        ];
    }

    private function fetchAverageFileCountForContainersFromDb() {
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

    private function fetchTotalFileCountFromDb() {
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

    private function fetchTotalFileSizeFromDb() {
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

    private function getAllContainers() {
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