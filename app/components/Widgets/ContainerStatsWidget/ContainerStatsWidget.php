<?php

namespace App\Components\Widgets\ContainerStatsWidget;

use App\Components\Widgets\Widget;
use App\Constants\ContainerStatus;
use App\Core\Http\HttpRequest;
use App\Managers\ContainerManager;

/**
 * Widget with container statistics
 * 
 * @author Lukas Velek
 */
class ContainerStatsWidget extends Widget {
    private ContainerManager $cm;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param ContainerManager $cm ContainerManager instance
     */
    public function __construct(HttpRequest $request, ContainerManager $cm) {
        parent::__construct($request);

        $this->cm = $cm;
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();

        $this->setData($data);
        $this->setTitle('Container statistics');
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
            'All containers' => $data['totalCount'],
            'New containers' => $data['newCount'],
            'Running containers' => $data['runningCount'],
            'Not running containers' => $data['notRunningCount'],
        ];

        return $rows;
    }

    /**
     * Fetches data from the database
     * 
     * @param array Data rows
     */
    private function fetchDataFromDb() {
        $totalCount = $this->fetchTotalContainerCountFromDb();
        $newCount = $this->fetchNewContainerCountFromDb();
        $runningCount = $this->fetchRunningContainerCountFromDb();
        $notRunningCount = $this->fetchNotRunningContainerCountFromDb();

        return [
            'totalCount' => $totalCount,
            'newCount' => $newCount,
            'runningCount' => $runningCount,
            'notRunningCount' => $notRunningCount
        ];
    }

    /**
     * Fetches total container count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchTotalContainerCountFromDb() {
        $qb = $this->cm->containerRepository->composeQueryForContainers();
        $qb->select(['COUNT(*) AS cnt']);
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches new container count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchNewContainerCountFromDb() {
        $qb = $this->cm->containerRepository->composeQueryForContainers();
        $qb->select(['COUNT(*) AS cnt'])
            ->andWhere('status = ?', [ContainerStatus::NEW]);
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches running container count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchRunningContainerCountFromDb() {
        $qb = $this->cm->containerRepository->composeQueryForContainers();
        $qb->select(['COUNT(*) AS cnt'])
            ->andWhere('status = ?', [ContainerStatus::RUNNING]);
        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches not running container count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchNotRunningContainerCountFromDb() {
        $qb = $this->cm->containerRepository->composeQueryForContainers();
        $qb->select(['COUNT(*) AS cnt'])
            ->andWhere('status = ?', [ContainerStatus::NOT_RUNNING]);
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