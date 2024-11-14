<?php

namespace App\Modules\AdminModule;

use App\Components\Navbar\Navbar;
use App\Components\Navbar\NavbarModes;
use App\Modules\AContainerModule;

class AdminModule extends AContainerModule {
    public function __construct() {
        parent::__construct('AdminModule');

        $this->navbarMode = NavbarModes::ADMINISTRATION;
    }

    public function renderModule() {
        parent::renderModule();

        if($this->template !== null) {
            $this->template->sys_navbar = $this->navbar;
        }
    }
}

?>