<?php

namespace App\Modules\SuperAdminModule;

use App\Components\GlobalUsageStatsGraph\GlobalUsageStatsGraph;
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
}

?>