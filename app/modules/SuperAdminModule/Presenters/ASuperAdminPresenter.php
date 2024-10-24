<?php

namespace App\Modules\SuperAdminModule;

use App\Components\Navbar\Navbar;
use App\Components\Navbar\NavbarModes;
use App\Modules\APresenter;

abstract class ASuperAdminPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);
    }

    public function startup() {
        parent::startup();

        $this->template->sys_navbar = new Navbar(NavbarModes::SUPERADMINISTRATION, $this->getUser());
    }
}

?>