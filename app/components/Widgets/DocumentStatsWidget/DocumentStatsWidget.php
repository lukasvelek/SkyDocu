<?php

namespace App\Components\Widgets\DocumentStatsWidget;

use App\Components\Widgets\Widget;
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
    }

    /**
     * Processes widget data
     * 
     * @return array Widget rows
     */
    private function processData() {
        $data = $this->fetchDataFromDb();

        $rows = [
            'Total documents' => $data['totalRows']
        ];

        return $rows;
    }

    /**
     * Fetches data from the database
     * 
     * @param array Data rows
     */
    private function fetchDataFromDb() {
        $qb = $this->dm->dr->composeQueryForDocuments();

        $qb->select(['COUNT(*) AS cnt']);

        $totalRows = $qb->execute()->fetch('cnt');

        return [
            'totalRows' => $totalRows
        ];
    }
}

?>