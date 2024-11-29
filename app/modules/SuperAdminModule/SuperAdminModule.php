<?php

namespace App\Modules\SuperAdminModule;

use App\Components\Navbar\NavbarModes;
use App\Modules\AModule;

class SuperAdminModule extends AModule {
    public function __construct() {
        parent::__construct('SuperAdminModule');
    }

    protected function createComponentSysNavbar() {
        return $this->createNavbarInstance(NavbarModes::SUPERADMINISTRATION, null);
    }
}

?>