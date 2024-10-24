<?php

namespace App\Modules\SuperAdminModule;

class HomePresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function handleHome() {}
}

?>