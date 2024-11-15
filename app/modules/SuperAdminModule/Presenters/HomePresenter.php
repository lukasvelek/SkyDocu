<?php

namespace App\Modules\SuperAdminModule;

use App\Components\Widgets\ContainerStatsWidget\ContainerStatsWidget;
use App\Core\Http\HttpRequest;

class HomePresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function renderHome() {

    }

    protected function createComponentContainerStatsWidget(HttpRequest $request) {
        $widget = new ContainerStatsWidget($request, $this->app->containerManager);

        return $widget;
    }
}

?>