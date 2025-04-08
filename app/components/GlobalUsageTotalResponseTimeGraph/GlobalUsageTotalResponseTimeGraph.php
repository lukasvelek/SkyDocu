<?php

namespace App\Components\GlobalUsageTotalResponseTimeGraph;

use App\Components\Graph\AGraph;
use App\Constants\ContainerStatus;
use App\Core\Datetypes\DateTime;
use App\Core\Http\HttpRequest;
use App\Repositories\ContainerRepository;
use App\UI\AComponent;

class GlobalUsageTotalResponseTimeGraph extends AGraph {
    private ContainerRepository $containerRepository;

    public function __construct(HttpRequest $request, ContainerRepository $containerRepository) {
        parent::__construct($request);

        $this->containerRepository = $containerRepository;

        $this->title = 'Global usage total response time';
        $this->numberOfColumns = 7;

        $this->setCanvasName('globalUsageTotalSqlServerTime');
        $this->setValueDescription('[s] Server time');
    }

    public static function createFromComponent(AComponent $component) {}

    /**
     * Fetches data from the database and formats it for further processing
     * 
     * @return array Data from the database
     */
    private function getData() {
        $qb = $this->containerRepository->composeQueryForContainers();
        $qb->andWhere($qb->getColumnInValues('status', [ContainerStatus::NOT_RUNNING, ContainerStatus::RUNNING]))
            ->execute();

        $containers = [];
        while($row = $qb->fetchAssoc()) {
            $containers[] = $row['containerId'];
        }

        $entries = [];
        foreach($containers as $containerId) {
            $qb = $this->containerRepository->composeQueryForContainerUsageStatistics($containerId);

            $dateFrom = new DateTime();
            $dateFrom->modify('-' . $this->numberOfColumns . 'd');
            $dateFrom = $dateFrom->getResult();

            $qb->orderBy('date', 'DESC')
                ->limit($this->numberOfColumns)
                ->execute();

            while($row = $qb->fetchAssoc()) {
                if(array_key_exists($row['date'], $entries)) {
                    $entries[$row['date']] = (int)($entries[$row['date']] + $row['totalTimeTaken']);
                } else {
                    $entries[$row['date']] = (int)$row['totalTimeTaken'];
                }
            }
        }

        return $entries;
    }

    protected function formatData(): string {
        $entries = $this->getData();

        $rows = [];
        foreach($entries as $date => $total) {
            if(count($rows) > $this->numberOfColumns) {
                break;
            }

            $date = new DateTime(strtotime($date));
            $date->format('d.m.Y');
            $date = $date->getResult();

            $row = '{ date: "' . $date . '", queryCount: ' . $total . ' }';
            array_unshift($rows, $row);
        }

        return implode(', ', $rows);
    }
}

?>