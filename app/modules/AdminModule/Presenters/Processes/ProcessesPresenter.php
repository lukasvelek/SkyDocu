<?php

namespace App\Modules\AdminModule;

use App\Components\Widgets\ProcessStatsWidget\ProcessStatsWidget;
use App\Core\Http\HttpRequest;

class ProcessesPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');

        $this->setProcesses();
    }

    public function renderDashboard() {}

    protected function createComponentProcessStatsWidget(HttpRequest $request) {
        $widget = new ProcessStatsWidget($request, $this->processRepository);

        return $widget;
    }
}

?>