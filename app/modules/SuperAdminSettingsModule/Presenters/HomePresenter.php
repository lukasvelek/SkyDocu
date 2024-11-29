<?php

namespace App\Modules\SuperAdminSettingsModule;

class HomePresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function handleDashboard() {}
}

?>