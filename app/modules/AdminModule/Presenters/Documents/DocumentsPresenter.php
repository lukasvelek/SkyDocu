<?php

namespace App\Modules\AdminModule;

use App\Components\Widgets\DocumentStatsWidget\DocumentStatsWidget;
use App\Core\Http\HttpRequest;

class DocumentsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('DocumentsPresenter', 'Documents');

        $this->setDocuments();
    }

    public function renderDashboard() {}

    protected function createComponentDocumentsStatsWidget(HttpRequest $request) {
        $widget = new DocumentStatsWidget($request, $this->documentManager);

        return $widget;
    }
}

?>