<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Components\Widgets\AboutApplicationWidget\AboutApplicationWidget;
use App\Core\Http\HttpRequest;

class AboutApplicationPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('AboutApplicationPresenter', 'About application');
    }

    public function renderDefault() {}

    protected function createComponentAboutApplicationWidget(HttpRequest $request) {
        $widget = new AboutApplicationWidget($request);

        return $widget;
    }
}

?>