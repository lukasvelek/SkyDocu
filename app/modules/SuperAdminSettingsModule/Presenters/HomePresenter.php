<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Components\Widgets\BackgroundServiceStatsWidget\BackgroundServiceStatsWidget;
use App\Components\Widgets\ContainerStatsWidget\ContainerStatsWidget;
use App\Core\Http\HttpRequest;

class HomePresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function handleDashboard() {}

    public function renderDashboard() {}

    protected function createComponentContainerStatsWidget(HttpRequest $request) {
        $widget = new ContainerStatsWidget($request, $this->app->containerManager);

        return $widget;
    }

    protected function createComponentBgServicesStatsWidget(HttpRequest $request) {
        $widget = new BackgroundServiceStatsWidget($request, $this->app->systemServicesRepository);

        return $widget;
    }
}

?>