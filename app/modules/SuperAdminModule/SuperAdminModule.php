<?php

namespace App\Modules\SuperAdminModule;

use App\Components\Navbar\Navbar;
use App\Components\Navbar\NavbarModes;
use App\Modules\AModule;

class SuperAdminModule extends AModule {
    public ?Navbar $navbar;

    public function __construct() {
        parent::__construct('SuperAdminModule');

        $this->navbar = null;
    }

    protected function createComponentSysNavbar() {
        return $this->createNavbar();
    }

    private function createNavbar() {
        if($this->navbar === null) {
            $this->navbar = $this->createNavbarInstance(NavbarModes::SUPERADMINISTRATION, null);
        }

        return $this->navbar;
    }
}

?>