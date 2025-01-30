<?php

namespace App\Modules\AdminModule;

use App\Components\Widgets\AboutApplicationWidget\AboutApplicationWidget;
use App\Core\Http\HttpRequest;

class SystemPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('SystemPresenter', 'System');

        $this->setSystem();
    }

    public function renderDashboard() {}

    protected function createComponentAboutApplicationWidget(HttpRequest $request) {
        $widget = new AboutApplicationWidget($request);

        $widget->disableGithubLink();
        $widget->disablePHPVersion();

        return $widget;
    }
}

?>