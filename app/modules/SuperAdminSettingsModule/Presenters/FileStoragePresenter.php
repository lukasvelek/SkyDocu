<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Components\Widgets\FileStorageStatsWidget\FileStorageStatsWidget;
use App\Core\Http\HttpRequest;

class FileStoragePresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('FileStoragePresenter', 'File storage');
    }

    public function renderDashboard() {}

    protected function createComponentFileStorageStatsWidget(HttpRequest $request) {
        $widget = new FileStorageStatsWidget(
            $request,
            $this->app->containerManager,
            $this->app->dbManager,
            $this->logger
        );

        return $widget;
    }
}

?>