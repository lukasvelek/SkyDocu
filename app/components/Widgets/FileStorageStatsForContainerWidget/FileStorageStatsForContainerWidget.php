<?php

namespace App\Components\Widgets\FileStorageStatsForContainerWidget;

use App\Components\Widgets\Widget;
use App\Core\Http\HttpRequest;
use App\Helpers\UnitConversionHelper;
use App\Managers\Container\FileStorageManager;

/**
 * FileStorageStatsForContainerWidget display statistics for container file storage
 * 
 * @author Lukas Velek
 */
class FileStorageStatsForContainerWidget extends Widget {
    private FileStorageManager $fileStorageManager;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request
     * @param FileStorageManager $fileStorageManager
     */
    public function __construct(HttpRequest $request, FileStorageManager $fileStorageManager) {
        parent::__construct($request);

        $this->fileStorageManager = $fileStorageManager;
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();

        $this->setData($data);
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
            'Total file size' => $data['totalSize']
        ];

        return $rows;
    }

    /**
     * Fetches data from database
     */
    private function fetchDataFromDb(): array {
        $totalFiles = $this->fetchTotalFileCountFromDb();
        $totalFileSize = $this->processTotalFileSize();

        return [
            'totalRows' => $totalFiles,
            'totalSize' => $totalFileSize
        ];
    }

    /**
     * Fetches total file count from database
     */
    private function fetchTotalFileCountFromDb(): mixed {
        $qb = $this->fileStorageManager->fileStorageRepository->composeQueryForStoredFiles();
        $qb->select(['COUNT(*) AS cnt'])
            ->regenerateSQL()
            ->execute();
        return $qb->fetch('cnt');
    }

    /**
     * Processes total file size
     */
    private function processTotalFileSize(): string {
        $qb = $this->fileStorageManager->fileStorageRepository->composeQueryForStoredFiles();
        $qb->execute();
        $size = 0;
        while($row = $qb->fetchAssoc()) {
            $size += (int)($row['filesize']);
        }

        return UnitConversionHelper::convertBytesToUserFriendly($size);
    }
}

?>