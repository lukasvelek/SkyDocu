<?php

namespace App\Modules\ErrorModule;

class E404Presenter extends AErrorPresenter {
    public function __construct() {
        parent::__construct('E404Presenter', '404 Not Found');

        $this->setDefaultAction('default');
    }

    public function renderDefault() {}
}

?>