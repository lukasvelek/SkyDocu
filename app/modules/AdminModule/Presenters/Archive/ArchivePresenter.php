<?php

namespace App\Modules\AdminModule;

use App\Components\Widgets\ArchiveStatsWidget\ArchiveStatsWidget;
use App\Core\Http\HttpRequest;

class ArchivePresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ArchivePresenter', 'Archive');

        $this->setArchive();
    }

    public function renderDashboard() {}

    protected function createComponentArchiveStatsWidget(HttpRequest $request) {
        $widget = new ArchiveStatsWidget($request, $this->archiveManager);

        return $widget;
    }
}

?>