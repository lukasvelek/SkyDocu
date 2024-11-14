<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Components\Navbar\Navbar;
use App\Components\Navbar\NavbarModes;
use App\Modules\AModule;

class SuperAdminSettingsModule extends AModule {
    public function __construct(){
        parent::__construct('SuperAdminSettingsModule');
    }

    public function renderModule() {
        $navbar = new Navbar(NavbarModes::SUPERADMINISTRATION_SETTINGS, $this->app->currentUser, $this->app, null);
        $navbar->startup();
        if($this->template !== null) {
            $this->template->sys_navbar = $navbar;
        }
    }
}

?>