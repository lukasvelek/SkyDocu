<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Components\Navbar\NavbarModes;
use App\Modules\AModule;

class SuperAdminSettingsModule extends AModule {
    public function __construct(){
        parent::__construct('SuperAdminSettingsModule');
    }

    protected function createComponentSysNavbar() {
        return $this->createNavbarInstance(NavbarModes::SUPERADMINISTRATION_SETTINGS);
    }
}

?>