<?php

namespace App\Components\ContainerUsageTotalResponseTimeGraph;

use App\Components\Graph\AGraph;
use App\Core\Datetypes\DateTime;
use App\Core\Http\HttpRequest;
use App\Exceptions\GeneralException;
use App\Repositories\ContainerRepository;
use App\UI\AComponent;

/**
 * Graph that displays container total response time
 * 
 * @author Lukas Velek
 */
class ContainerUsageTotalResponseTimeGraph extends AGraph {
    private ?string $containerId;
    private ContainerRepository $containerRepository;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param ContainerRepository $containerRepository ContainerRepository instance
     */
    public function __construct(HttpRequest $request, ContainerRepository $containerRepository) {
        parent::__construct($request);

        $this->containerRepository = $containerRepository;
        $this->title = 'Container usage total SQL server time';
        $this->containerId = null;
        $this->numberOfColumns = 7;
    }

    /**
     * Sets the container ID
     * 
     * @param string $containerId Container ID
     */
    public function setContainerId(string $containerId) {
        $this->containerId = $containerId;
    }

    public static function createFromComponent(AComponent $component) {}

    /**
     * Fetches data from the database and formats it for further processing
     * 
     * @return array Data from the database
     */
    private function getData() {
        if($this->containerId === null) {
            throw new GeneralException('No container ID passed.', null, false);
        }

        $qb = $this->containerRepository->composeQueryForContainerUsageStatistics($this->containerId);

        $dateFrom = new DateTime();
        $dateFrom->modify('-' . $this->numberOfColumns . 'd');
        $dateFrom = $dateFrom->getResult();

        $qb->orderBy('date', 'DESC')
            ->limit($this->numberOfColumns)
            ->execute();

        $entries = [];
        while($row = $qb->fetchAssoc()) {
            $entries[$row['date']] = $row['totalTimeTaken'];
        }

        return $entries;
    }

    protected function formatData(): string {
        $entries = $this->getData();

        $rows = [];
        foreach($entries as $date => $total) {
            $date = new DateTime(strtotime($date));
            $date->format('d.m.Y');
            $date = $date->getResult();

            $total = round($total / 60); // ms to s and then round

            $row = '{ date: "' . $date . '", queryCount: ' . $total . ' }';
            array_unshift($rows, $row);
        }

        return implode(', ', $rows);
    }
}

?>