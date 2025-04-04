<?php

namespace App\Modules\SuperAdminModule;

use App\Components\GlobalUsageAverageResponseTimeGraph\GlobalUsageAverageResponseTimeGraph;
use App\Components\GlobalUsageStatsGraph\GlobalUsageStatsGraph;
use App\Components\GlobalUsageTotalResponseTimeGraph\GlobalUsageTotalResponseTimeGraph;
use App\Core\Http\HttpRequest;

class StatisticsPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('StatisticsPresenter', 'Statistics');
    }

    public function renderDashboard() {}

    protected function createComponentUsageStatsGraph(HttpRequest $request) {
        $graph = new GlobalUsageStatsGraph($request, $this->app->containerRepository);

        $graph->setCanvasWidth(400);

        return $graph;
    }

    protected function createComponentUsageAverageResponseTimeGraph(HttpRequest $request) {
        $graph = new GlobalUsageAverageResponseTimeGraph($request, $this->app->containerRepository);

        $graph->setCanvasWidth(400);

        return $graph;
    }

    protected function createComponentUsageTotalResponseTimeGraph(HttpRequest $request) {
        $graph = new GlobalUsageTotalResponseTimeGraph($request, $this->app->containerRepository);

        $graph->setCanvasWidth(400);

        return $graph;
    }
}

?>