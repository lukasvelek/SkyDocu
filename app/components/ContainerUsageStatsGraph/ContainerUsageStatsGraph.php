<?php

namespace App\Components\ContainerUsageStatsGraph;

use App\Core\Datetypes\DateTime;
use App\Core\Http\HttpRequest;
use App\Exceptions\GeneralException;
use App\Repositories\ContainerRepository;
use App\UI\AComponent;

class ContainerUsageStatsGraph extends AComponent {
    private ?string $containerId;
    private string $title;
    private ContainerRepository $containerRepository;
    private int $numberOfColumns;
    private int $canvasWidth;

    public function __construct(HttpRequest $request, ContainerRepository $containerRepository) {
        parent::__construct($request);

        $this->containerRepository = $containerRepository;
        $this->title = 'Container usage statistics';
        $this->containerId = null;
        $this->numberOfColumns = 7;
        $this->canvasWidth = 500;
    }

    public function setCanvasWidth(int $canvasWidth) {
        $this->canvasWidth = $canvasWidth;
    }

    public function setContainerId(string $containerId) {
        $this->containerId = $containerId;
    }

    public function setNumberOfColumns(int $columns) {
        $this->numberOfColumns = $columns;
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '/template.html');
        $template->title = $this->title;
        $template->scripts = $this->createJSScripts();
        $template->canvas_width = $this->canvasWidth;

        return $template->render()->getRenderedContent();
    }

    public static function createFromComponent(AComponent $component) {}

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
            $entries[$row['date']] = $row['totalSqlQueries'];
        }

        return $entries;
    }

    private function createJSScripts() {
        $codes = [];

        $addScript = function(string $code) use (&$codes) {
            $codes[] = '<script type="text/javascript">' . $code . '</script>';
        };

        $addScript('
            (async function() {
                const _data = [' . $this->getFormattedData() . '];

                new Chart(document.getElementById("canvas_containerUsageStats"), 
                {
                    type: "bar",
                    data: {
                        labels: _data.map(row => row.date),
                        datasets: [{
                            label: "Database queries",
                            data: _data.map(row => row.queryCount)
                        }]
                    }
                });
            })();
        ');

        return implode('', $codes);
    }

    private function getFormattedData() {
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