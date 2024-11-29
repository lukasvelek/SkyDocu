<?php

namespace App\Modules\AdminModule;

class SystemPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('SystemPresenter', 'System');

        $this->setSystem();
    }

    public function renderDashboard() {}
}

?>