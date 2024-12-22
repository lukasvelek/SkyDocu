<?php

namespace App\Components\Widgets\ProcessStatsWidget;

use App\Components\Widgets\Widget;
use App\Constants\Container\ProcessStatus;
use App\Core\Http\HttpRequest;
use App\Repositories\Container\ProcessRepository;

/**
 * Widget with process statistics
 * 
 * @author Lukas Velek
 */
class ProcessStatsWidget extends Widget {
    private ProcessRepository $pr;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param ProcessRepository $pr ProcessRepository instance
     */
    public function __construct(HttpRequest $request, ProcessRepository $pr) {
        parent::__construct($request);

        $this->pr = $pr;
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();

        $this->setData($data);
        $this->setTitle('Process statistics');
        $this->enableRefresh();
    }

    /**
     * Processes data and defines rows
     * 
     * @return array<string, mixed> Processed data
     */
    private function processData() {
        $data = $this->fetchDataFromDb();

        $rows = [
            'All processes' => $data['total'],
            'Processes in progress' => $data['inProgress'],
            'Finished processes' => $data['finished'],
            'Canceled processes' => $data['canceled']
        ];

        return $rows;
    }

    /**
     * Fetches data from the database
     * 
     * @return array<string, mixed> Data from the database
     */
    private function fetchDataFromDb() {
        $total = $this->fetchTotalProcessCountFromDb();
        $inProgress = $this->fetchInProgressProcessCountFromDb();
        $finished = $this->fetchFinishedProcessCountFromDb();
        $canceled = $this->fetchCanceledProcessCountFromDb();

        $rows = [
            'total' => $total,
            'inProgress' => $inProgress,
            'finished' => $finished,
            'canceled' => $canceled
        ];

        return $rows;
    }

    /**
     * Fetches total process count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchTotalProcessCountFromDb() {
        $qb = $this->pr->composeQueryForStandaloneProcesses();
        $qb->select(['COUNT(*) AS cnt']);

        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches in progress process count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchInProgressProcessCountFromDb() {
        $qb = $this->pr->composeQueryForStandaloneProcesses();
        $qb->select(['COUNT(*) AS cnt'])
            ->andWhere('status = ?', [ProcessStatus::IN_PROGRESS]);

        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches finished process count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchFinishedProcessCountFromDb() {
        $qb = $this->pr->composeQueryForStandaloneProcesses();
        $qb->select(['COUNT(*) AS cnt'])
            ->andWhere('status = ?', [ProcessStatus::FINISHED]);

        return $qb->execute()->fetch('cnt');
    }

    /**
     * Fetches canceled process count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchCanceledProcessCountFromDb() {
        $qb = $this->pr->composeQueryForStandaloneProcesses();
        $qb->select(['COUNT(*) AS cnt'])
            ->andWhere('status = ?', [ProcessStatus::CANCELED]);

        return $qb->execute()->fetch('cnt');
    }

    public function actionRefresh() {
        $data = $this->processData();
        $this->setData($data);

        return parent::actionRefresh();
    }
}

?>