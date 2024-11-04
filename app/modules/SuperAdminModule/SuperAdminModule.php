<?php

namespace App\Modules\SuperAdminModule;

use App\Components\Navbar\Navbar;
use App\Components\Navbar\NavbarModes;
use App\Modules\AModule;

class SuperAdminModule extends AModule {
    public function __construct() {
        parent::__construct('SuperAdminModule');
    }

    public function renderModule() {
        $navbar = new Navbar(NavbarModes::SUPERADMINISTRATION, $this->app->currentUser, $this->app);
        if($this->template !== null) {
            $this->template->sys_navbar = $navbar;
        }
    }
}

?>