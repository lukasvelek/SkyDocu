<?php

namespace App\Modules;

use App\Components\Navbar\Navbar;
use App\Managers\Container\GroupManager;
use App\Managers\EntityManager;
use App\Repositories\Container\GroupRepository;
use App\Repositories\ContentRepository;
use App\Repositories\UserRepository;

abstract class AContainerModule extends AModule {
    protected string $navbarMode;
    protected Navbar $navbar;

    private GroupManager $groupManager;

    public function __construct(string $title) {
        parent::__construct($title);
    }

    protected function startup(string $presenterTitle, string $actionTitle) {
        parent::startup($presenterTitle, $actionTitle);

        $containerId = $this->httpSessionGet('container');
        $container = $this->app->containerManager->getContainerById($containerId);
        $db = $this->app->dbManager->getConnectionToDatabase($container->databaseName);

        $contentRepository = new ContentRepository($db, $this->logger);

        $entityManager = new EntityManager($this->logger, $contentRepository);
        $groupRepository = new GroupRepository($db, $this->logger);
        $userRepository = new UserRepository($db, $this->logger);

        $this->groupManager = new GroupManager($this->logger, $entityManager, $groupRepository, $userRepository);

        $this->navbar = new Navbar($this->navbarMode, $this->app->currentUser, $this->app, $this->groupManager);
    }

    public function renderModule() {
        parent::renderModule();

        $this->navbar->inject($this->groupManager);
        $this->navbar->startup();
    }
}

?>