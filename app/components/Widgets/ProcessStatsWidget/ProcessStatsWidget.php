<?php

namespace App\Components\Widgets\ProcessStatsWidget;

use App\Components\Widgets\Widget;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Managers\Container\ProcessInstanceManager;
use App\Managers\Container\ProcessManager;

/**
 * ProcessStatsWidget displays statistics for container processes and instances
 * 
 * @author Lukas Velek
 */
class ProcessStatsWidget extends Widget {
    private ProcessManager $processManager;
    private ProcessInstanceManager $processInstanceManager;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param ProcessManager $processManager ProcessManager instance
     * @param ProcessInstanceManager $processInstanceManager ProcessInstanceManager instance
     */
    public function __construct(
        HttpRequest $request,
        ProcessManager $processManager,
        ProcessInstanceManager $processInstanceManager
    ) {
        parent::__construct($request);

        $this->processManager = $processManager;
        $this->processInstanceManager = $processInstanceManager;
    }

    public function startup() {
        parent::startup();

        $this->setData($this->processData());
        $this->setTitle('Process statistics');
        $this->enableRefresh();
    }

    /**
     * Processes widget data
     */
    private function processData(): array {
        $data = $this->fetchDataFromDb();

        $rows = [
            'Distribution processes' => $data['distributionProcesses'],
            'Custom processes' => $data['customProcesses'],
            'Active process instances' => $data['activeProcessInstances']
        ];

        return $rows;
    }

    /**
     * Fetches data from the database
     */
    private function fetchDataFromDb(): array {
        $distributionProcesses = 0;
        $customProcesses = 0;
        $activeProcessInstances = 0;

        try {
            $data1 = $this->fetchProcessCount();
            $distributionProcesses = $data1['distribution'];
            $customProcesses = $data1['custom'];

            $activeProcessInstances = $this->fetchActiveProcessInstances();
        } catch(AException $e) {}

        return [
            'distributionProcesses' => $distributionProcesses,
            'customProcesses' => $customProcesses,
            'activeProcessInstances' => $activeProcessInstances
        ];
    }

    /**
     * Fetches process count
     */
    private function fetchProcessCount(): array {
        $qb = $this->processManager->processRepository->composeQueryForAvailableProcesses();
        $qb->execute();

        $customProcesses = [];
        $distributionProcesses = [];
        while($row = $qb->fetchAssoc()) {
            if($row['status'] == 1) {
                $distributionProcesses[] = $row;
            } else if($row['status'] == 4) {
                $customProcesses[] = $row;
            }
        }

        return [
            'custom' => count($customProcesses),
            'distribution' => count($distributionProcesses)
        ];
    }

    /**
     * Fetches count of active process instances
     */
    private function fetchActiveProcessInstances() {
        $qb = $this->processInstanceManager->processInstanceRepository->commonComposeQuery();
        $qb->select(['COUNT(instanceId) AS cnt'])
            ->andWhere($qb->getColumnInValues('status', [1, 2]))
            ->execute();

        return $qb->fetch('cnt');
    }
}