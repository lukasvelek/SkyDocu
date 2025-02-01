<?php

namespace App\Components\Widgets\ArchiveStatsWidget;

use App\Components\Widgets\Widget;
use App\Constants\Container\ArchiveFolderStatus;
use App\Core\Http\HttpRequest;
use App\Managers\Container\ArchiveManager;
use QueryBuilder\QueryBuilder;

/**
 * Widget with archive statistics
 * 
 * @author Lukas Velek
 */
class ArchiveStatsWidget extends Widget {
    private ArchiveManager $archiveManager;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param ArchiveManager $archiveManager ArchiveManager instance
     */
    public function __construct(HttpRequest $request, ArchiveManager $archiveManager) {
        parent::__construct($request);

        $this->archiveManager = $archiveManager;
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();

        $this->setData($data);
        $this->setTitle('Archive statistics');
        $this->enableRefresh();
    }

    /**
     * Processes widget data
     * 
     * @return array Widget rows
     */
    private function processData() {
        $data = $this->fetchDataFromDb();

        $rows = [
            'All archive folders' => $data['totalRows'],
            'Archived archive folders' => $data['archivedRows'],
            'Archived documents' => $data['archivedDocumentCount']
        ];

        return $rows;
    }

    /**
     * Fetches data from the database
     * 
     * @return array Data rows
     */
    private function fetchDataFromDb() {
        $totalRows = $this->fetchTotalArchiveCountFromDb();
        $archivedRows = $this->fetchArchivedArchiveCountFromDb();
        $archivedDocumentCount = $this->fetchTotalArchivedDocumentCountFromDb();

        return [
            'totalRows' => $totalRows,
            'archivedRows' => $archivedRows,
            'archivedDocumentCount' => $archivedDocumentCount
        ];
    }

    /**
     * Fetches total archive count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchTotalArchiveCountFromDb() {
        $qb = $this->composeQuery();
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches archived archive count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchArchivedArchiveCountFromDb() {
        $qb = $this->composeQuery()
            ->where('status = ?', [ArchiveFolderStatus::ARCHIVED]);
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches total archived document count from the database
     * 
     * @return int Archived document count
     */
    private function fetchTotalArchivedDocumentCountFromDb(): int {
        $archiveFolders = $this->archiveManager->getAllArchiveFolders(false);

        $documentIds = [];
        foreach($archiveFolders as $archiveFolder) {
            $tmp = $this->archiveManager->getDocumentsForArchiveFolder($archiveFolder->folderId);

            $documentIds = array_merge($documentIds, $tmp);
        }

        return count($documentIds);
    }

    /**
     * Composes query for counting purposes
     */
    private function composeQuery(): QueryBuilder {
        $qb = $this->archiveManager->archiveRepository->composeQueryForArchiveFolders();
        $qb->select(['COUNT(*) AS cnt']);
        return $qb;
    }

    public function actionRefresh() {
        $data = $this->processData();
        $this->setData($data);

        return parent::actionRefresh();
    }
}

?>