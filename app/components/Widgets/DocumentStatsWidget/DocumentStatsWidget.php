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
    private DocumentManager $dm;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param DocumentManager $dm DocumentManager instance
     */
    public function __construct(HttpRequest $request, DocumentManager $dm) {
        parent::__construct($request);

        $this->dm = $dm;
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
            'Total documents' => $data['totalRows'],
            'New documents' => $data['newRows'],
            'Archived documents' => $data['archivedRows']
        ];

        return $rows;
    }

    /**
     * Fetches data from the database
     * 
     * @param array Data rows
     */
    private function fetchDataFromDb() {
        $totalRows = $this->fetchTotalDocumentCountFromDb();
        $newRows = $this->fetchNewDocumentCountFromDb();
        $archivedRows = $this->fetchArchivedDocumentCountFromDb();

        return [
            'totalRows' => $totalRows,
            'newRows' => $newRows,
            'archivedRows' => $archivedRows
        ];
    }

    /**
     * Fetches total document count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchTotalDocumentCountFromDb() {
        $qb = $this->dm->dr->composeQueryForDocuments();
        $qb->select(['COUNT(*) AS cnt']);
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches new document count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchNewDocumentCountFromDb() {
        $qb = $this->dm->dr->composeQueryForDocuments();
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
        $qb = $this->dm->dr->composeQueryForDocuments();
        $qb->select(['COUNT(*) AS cnt'])
            ->where('status = ?', [DocumentStatus::ARCHIVED]);
        return $qb->execute()->fetch('cnt');
    }

    public function actionRefresh() {
        $data = $this->processData();
        $this->setData($data);

        $widget = $this->build();

        return [
            'widget' => $widget
        ];
    }
}

?>