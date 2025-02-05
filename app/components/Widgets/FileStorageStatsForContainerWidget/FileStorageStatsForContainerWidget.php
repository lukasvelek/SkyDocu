<?php

namespace App\Components\Widgets\FileStorageStatsForContainerWidget;

use App\Components\Widgets\Widget;
use App\Core\Http\HttpRequest;
use App\Helpers\UnitConversionHelper;
use App\Managers\Container\FileStorageManager;

class FileStorageStatsForContainerWidget extends Widget {
    private FileStorageManager $fileStorageManager;

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

    private function processData() {
        $data = $this->fetchDataFromDb();

        $rows = [
            'All files' => $data['totalRows'],
            'Total file size' => $data['totalSize']
        ];

        return $rows;
    }

    private function fetchDataFromDb() {
        $totalFiles = $this->fetchTotalFileCountFromDb();
        $totalFileSize = $this->processTotalFileSize();

        return [
            'totalRows' => $totalFiles,
            'totalSize' => $totalFileSize
        ];
    }

    private function fetchTotalFileCountFromDb() {
        $qb = $this->fileStorageManager->fileStorageRepository->composeQueryForStoredFiles();
        $qb->select(['COUNT(*) AS cnt'])
            ->regenerateSQL()
            ->execute();
        return $qb->fetch('cnt');
    }

    private function processTotalFileSize() {
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