<?php

namespace App\Modules\UserModule;

use App\Components\Navbar\NavbarModes;
use App\Modules\AContainerModule;

class UserModule extends AContainerModule {
    public function __construct() {
        parent::__construct('UserModule');

        $this->navbarMode = NavbarModes::GENERAL;
    }

    public function renderModule() {
        parent::renderModule();
        
        if($this->template !== null) {
            $this->template->sys_navbar = $this->navbar;
        }
    }
}

?>