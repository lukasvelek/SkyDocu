<?php

namespace App\Modules\UserModule;

use App\Components\Navbar\Navbar;
use App\Components\Navbar\NavbarModes;
use App\Modules\AModule;

class UserModule extends AModule {
    public function __construct() {
        parent::__construct('UserModule');
    }

    public function renderModule() {
        $navbar = new Navbar(NavbarModes::GENERAL, $this->app->currentUser, $this->app);
        if($this->template !== null) {
            $this->template->sys_navbar = $navbar;
        }
    }
}

?>