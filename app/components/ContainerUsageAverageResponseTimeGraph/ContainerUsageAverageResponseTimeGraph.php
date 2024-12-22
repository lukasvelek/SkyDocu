<?php

namespace App\Components\ContainerUsageAverageResponseTimeGraph;

use App\Components\Graph\AGraph;
use App\Core\Datetypes\DateTime;
use App\Core\Http\HttpRequest;
use App\Exceptions\GeneralException;
use App\Repositories\ContainerRepository;
use App\UI\AComponent;

/**
 * Graph that displays average response time of containers
 * 
 * @author Lukas Velek
 */
class ContainerUsageAverageResponseTimeGraph extends AGraph {
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

        $this->title = 'Container usage average response time';
        $this->containerId = null;
        $this->numberOfColumns = 7;
        $this->canvasWidth = 500;

        $this->setCanvasName('containerUsageAverageResponseTime');
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
            $entries[$row['date']] = $row['averageTimeTaken'];
        }

        return $entries;
    }

    /**
     * Formats data fetched from the database to JSON format
     * 
     * @return string Data from the DB formatted to JSON
     */
    protected function formatData(): string {
        $entries = $this->getData();

        $rows = [];
        foreach($entries as $date => $total) {
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