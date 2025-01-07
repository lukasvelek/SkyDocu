<?php

namespace App\Components\Widgets\BackgroundServiceStatsWidget;

use App\Components\Widgets\Widget;
use App\Constants\SystemServiceStatus;
use App\Core\Http\HttpRequest;
use App\Repositories\SystemServicesRepository;

/**
 * Widget with background services statistics
 * 
 * @author Lukas Velek
 */
class BackgroundServiceStatsWidget extends Widget {
    private SystemServicesRepository $systemServicesRepository;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param SystemServicesRepository $systemServicesRepository SystemServicesRepository instance
     */
    public function __construct(HttpRequest $request, SystemServicesRepository $systemServicesRepository) {
        parent::__construct($request);

        $this->systemServicesRepository = $systemServicesRepository;

        $this->componentName = 'BackgroundServicesStatsWidget';
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();

        $this->setData($data);
        $this->setTitle('Background services statistics');
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
            'All services' => $data['totalCount'],
            'Running services' => $data['runningCount'],
            'Not running services' => $data['notRunningCount']
        ];

        return $rows;
    }

    /**
     * Fetches data from the database
     * 
     * @param array Data rows
     */
    private function fetchDataFromDb() {
        return [
            'totalCount' => $this->fetchTotalCountFromDb(),
            'runningCount' => $this->fetchRunningCountFromDb(),
            'notRunningCount' => $this->fetchNotRunningCountFromDb()
        ];
    }

    /**
     * Fetches total services count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchTotalCountFromDb() {
        $qb = $this->systemServicesRepository->composeQueryForServices();
        $qb->select(['COUNT(*) AS cnt']);
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches running services count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchRunningCountFromDb() {
        $qb = $this->systemServicesRepository->composeQueryForServices();
        $qb->select(['COUNT(*) AS cnt'])
            ->andWhere('status = ?', [SystemServiceStatus::RUNNING]);
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches not running services count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchNotRunningCountFromDb() {
        $qb = $this->systemServicesRepository->composeQueryForServices();
        $qb->select(['COUNT(*) AS cnt'])
            ->andWhere('status = ?', [SystemServiceStatus::NOT_RUNNING]);
        return $qb->execute()->fetch('cnt');
    }
}

?>