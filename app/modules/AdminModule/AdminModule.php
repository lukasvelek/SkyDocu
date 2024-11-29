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
}

?>