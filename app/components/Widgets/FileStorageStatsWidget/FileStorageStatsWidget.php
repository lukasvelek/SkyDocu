<?php

namespace App\Components\Widgets\FileStorageStatsWidget;

use App\Components\Widgets\Widget;
use App\Exceptions\AException;
use App\Helpers\UnitConversionHelper;

/**
 * FileStorageStatsWidget displays statistics for application file storage
 * 
 * @author Lukas Velek
 */
class FileStorageStatsWidget extends Widget {
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
            'Total file size' => $data['totalSize']
        ];

        return $rows;
    }

    /**
     * Fetches data from database
     */
    private function fetchDataFromDb(): array {
        $totalFiles = 0.0;
        $totalFileSize = 0.0;
        
        try {
            $totalFiles = $this->fetchTotalFileCountFromDb();
            $totalFileSize = $this->fetchTotalFileSizeFromDb();
        } catch(AException $e) {}

        return [
            'totalRows' => $totalFiles,
            'totalSize' => $totalFileSize
        ];
    }

    /**
     * Fetches total file count from database
     */
    private function fetchTotalFileCountFromDb(): int {
        $qb = $this->app->fileStorageRepository->composeQueryForFilesInStorage();
        $qb->select(['COUNT(*) AS cnt'])
            ->regenerateSQL()
            ->execute();

        return $qb->fetch('cnt') ?? 0;
    }

    /**
     * Fetches total file size from database
     */
    private function fetchTotalFileSizeFromDb(): string {
        $qb = $this->app->fileStorageRepository->composeQueryForFilesInStorage();
        $qb->select(['SUM(filesize) AS totalSize'])
            ->regenerateSQL()
            ->execute();

        $result = $qb->fetch('totalSize');

        if($result !== null) {
            return UnitConversionHelper::convertBytesToUserFriendly($result);
        }

        return '0 B';
    }
}

?>