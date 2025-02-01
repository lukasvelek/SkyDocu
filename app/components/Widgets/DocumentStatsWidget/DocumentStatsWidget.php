<?php

namespace App\Components\Widgets\DocumentStatsWidget;

use App\Components\Widgets\Widget;
use App\Constants\Container\DocumentStatus;
use App\Core\Http\HttpRequest;
use App\Managers\Container\DocumentManager;

/**
 * Widget with document statistics
 * 
 * @author Lukas Velek
 */
class DocumentStatsWidget extends Widget {
    private DocumentManager $documentManager;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param DocumentManager $documentManager DocumentManager instance
     */
    public function __construct(HttpRequest $request, DocumentManager $documentManager) {
        parent::__construct($request);

        $this->documentManager = $documentManager;
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();

        $this->setData($data);
        $this->setTitle('Document statistics');
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
            'All documents' => $data['totalRows'],
            'New documents' => $data['newRows'],
            'Archived documents' => $data['archivedRows'],
            'Shredded documents' => $data['shreddedRows']
        ];

        return $rows;
    }

    /**
     * Fetches data from the database
     * 
     * @return array Data rows
     */
    private function fetchDataFromDb() {
        $totalRows = $this->fetchTotalDocumentCountFromDb();
        $newRows = $this->fetchNewDocumentCountFromDb();
        $archivedRows = $this->fetchArchivedDocumentCountFromDb();
        $shreddedRows = $this->fetchShreddedDocumentCountFromDb();

        return [
            'totalRows' => $totalRows,
            'newRows' => $newRows,
            'archivedRows' => $archivedRows,
            'shreddedRows' => $shreddedRows
        ];
    }

    /**
     * Fetches total document count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchTotalDocumentCountFromDb() {
        $qb = $this->documentManager->documentRepository->composeQueryForDocuments();
        $qb->select(['COUNT(*) AS cnt']);
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches new document count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchNewDocumentCountFromDb() {
        $qb = $this->documentManager->documentRepository->composeQueryForDocuments();
        $qb->select(['COUNT(*) AS cnt'])
            ->where('status = ?', [DocumentStatus::NEW]);
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches archived document count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchArchivedDocumentCountFromDb() {
        $qb = $this->documentManager->documentRepository->composeQueryForDocuments();
        $qb->select(['COUNT(*) AS cnt'])
            ->where('status = ?', [DocumentStatus::ARCHIVED]);
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches shredded document count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchShreddedDocumentCountFromDb() {
        $qb = $this->documentManager->documentRepository->composeQueryForDocuments();
        $qb->select(['COUNT(*) AS cnt'])
            ->where('status = ?', [DocumentStatus::SHREDDED]);
        return $qb->execute()->fetch('cnt');
    }

    public function actionRefresh() {
        $data = $this->processData();
        $this->setData($data);

        return parent::actionRefresh();
    }
}

?>