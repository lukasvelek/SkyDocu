<?php

namespace App\Modules;

use App\Components\Navbar\Navbar;
use App\Constants\SessionNames;
use App\Core\Container;

abstract class AContainerModule extends AModule {
    protected string $navbarMode;
    protected Navbar $navbar;

    private Container $container;

    public function __construct(string $title) {
        parent::__construct($title);
    }

    protected function startup(string $presenterTitle, string $actionTitle) {
        parent::startup($presenterTitle, $actionTitle);

        $containerId = $this->httpSessionGet(SessionNames::CONTAINER);
        
        $this->container = new Container($this->app, $containerId);

        $this->navbar->inject($this->container->groupManager, $this->container->standaloneProcessManager);
    }

    /**
     * Creates the navbar component and saves it to the local $this->navbar variable and returns its link
     * 
     * @return Navbar
     */
    protected function createComponentSysNavbar() {
        $this->navbar = $this->createNavbarInstance($this->navbarMode);
        $navbar = &$this->navbar;

        return $navbar;
    }
}

?>