<?php

namespace App\Modules\AdminModule;

use App\Components\Navbar\Navbar;
use App\Components\Navbar\NavbarModes;
use App\Modules\AModule;

class AdminModule extends AModule {
    public function __construct() {
        parent::__construct('AdminModule');
    }

    public function renderModule() {
        $navbar = new Navbar(NavbarModes::ADMINISTRATION, $this->app->currentUser, $this->app);
        if($this->template !== null) {
            $this->template->sys_navbar = $navbar;
        }
    }
}

?>