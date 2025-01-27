<?php

namespace App\Modules\ErrorModule;

use App\Components\Navbar\Navbar;
use App\Components\Navbar\NavbarModes;
use App\Modules\AModule;

class ErrorModule extends AModule {
    public ?Navbar $navbar;

    public function __construct() {
        parent::__construct('ErrorModule');

        $this->navbar = null;
    }

    protected function createComponentSysNavbar() {
        return $this->createNavbar();
    }

    private function createNavbar() {
        $mode = null;

        if($this->httpRequest->query('calledPage') !== null) {
            $page = $this->httpRequest->query('calledPage');
            $module = explode(':', $page)[0];

            switch($module) {
                case 'AdminModule':
                    $mode = NavbarModes::ADMINISTRATION;
                    break;
                case 'AnonymModule':
                    $mode = NavbarModes::ANONYM;
                    break;

                case 'SuperAdminModule':
                    $mode = NavbarModes::SUPERADMINISTRATION;
                    break;

                case 'SuperAdminSettingsModule':
                    $mode = NavbarModes::SUPERADMINISTRATION_SETTINGS;
                    break;

                case 'UserModule':
                    $mode = NavbarModes::GENERAL;
                    break;
            }
        }

        if($this->navbar === null) {
            $this->navbar = $this->createNavbarInstance($mode, null);
        }

        return $this->navbar;
    }
}

?>