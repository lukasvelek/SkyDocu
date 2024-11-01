<?php

namespace App\Modules\UserModule;

use App\Core\DatabaseConnection;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\FolderManager;
use App\Managers\EntityManager;
use App\Modules\APresenter;
use App\Repositories\Container\DocumentClassRepository;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\Container\FolderRepository;
use App\Repositories\Container\GroupRepository;
use App\Repositories\ContentRepository;
use ReflectionClass;

abstract class AUserPresenter extends APresenter {
    protected array $cr;

    protected FolderRepository $folderRepository;
    protected GroupRepository $groupRepository;
    protected ContentRepository $contentRepository;
    protected DocumentRepository $documentRepository;
    protected DocumentClassRepository $documentClassRepository;
    
    protected EntityManager $entityManager;
    protected FolderManager $folderManager;
    protected DocumentManager $documentManager;

    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'User';

        $this->cr = [];
    }

    public function startup() {
        parent::startup();

        $containerId = $this->httpSessionGet('container');
        $container = $this->app->containerManager->getContainerById($containerId);
        $containerConnection = $this->app->dbManager->getConnectionToDatabase($container->databaseName);

        $this->initRepositories($containerConnection);

        $this->entityManager = new EntityManager($this->logger, $this->contentRepository);
        $this->folderManager = new FolderManager($this->logger, $this->entityManager, $this->folderRepository, $this->groupRepository);
        $this->documentManager = new DocumentManager($this->logger, $this->entityManager, $this->documentRepository, $this->documentClassRepository, $this->groupRepository, $this->folderRepository);
    }

    private function initRepositories(DatabaseConnection $db) {
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        foreach($rpa as $rp) {
            $rt = $rp->getType();

            if(str_contains($rt, 'Repository')) {
                $name = $rp->getName();
                $className = (string)$rt;

                $this->$name = new $className($db, $this->logger);
            }
        }
    }
}

?>