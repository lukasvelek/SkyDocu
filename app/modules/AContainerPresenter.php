<?php

namespace App\Modules;

use App\Authorizators\DocumentBulkActionAuthorizator;
use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Core\Caching\CacheFactory;
use App\Core\DatabaseConnection;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\EnumManager;
use App\Managers\Container\FolderManager;
use App\Managers\Container\GroupManager;
use App\Managers\Container\MetadataManager;
use App\Managers\EntityManager;
use App\Repositories\Container\DocumentClassRepository;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\Container\FolderRepository;
use App\Repositories\Container\GroupRepository;
use App\Repositories\Container\MetadataRepository;
use App\Repositories\Container\TransactionLogRepository;
use App\Repositories\ContentRepository;
use ReflectionClass;

abstract class AContainerPresenter extends APresenter {
    protected FolderRepository $folderRepository;
    protected GroupRepository $groupRepository;
    protected ContentRepository $contentRepository;
    protected DocumentRepository $documentRepository;
    protected DocumentClassRepository $documentClassRepository;
    protected MetadataRepository $metadataRepository;
    protected TransactionLogRepository $transactionLogRepository;
    
    protected EntityManager $entityManager;
    protected FolderManager $folderManager;
    protected DocumentManager $documentManager;
    protected GroupManager $groupManager;
    protected MetadataManager $metadataManager;
    protected EnumManager $enumManager;

    protected DocumentBulkActionAuthorizator $documentBulkActionAuthorizator;
    protected GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator;

    protected string $containerId;

    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);
    }

    public function startup() {
        parent::startup();

        $containerId = $this->httpSessionGet('container');
        $container = $this->app->containerManager->getContainerById($containerId);
        $containerConnection = $this->app->dbManager->getConnectionToDatabase($container->databaseName);

        $this->containerId = $containerId;

        $this->initRepositories($containerConnection);

        $this->entityManager = new EntityManager($this->logger, $this->contentRepository);
        $this->folderManager = new FolderManager($this->logger, $this->entityManager, $this->folderRepository, $this->groupRepository);
        $this->documentManager = new DocumentManager($this->logger, $this->entityManager, $this->documentRepository, $this->documentClassRepository, $this->groupRepository, $this->folderRepository);
        $this->groupManager = new GroupManager($this->logger, $this->entityManager, $this->groupRepository, $this->app->userRepository);
        $this->metadataManager = new MetadataManager($this->logger, $this->entityManager, $this->metadataRepository, $this->folderRepository);
        $this->enumManager = new EnumManager($this->logger, $this->entityManager, $this->app->userRepository, $this->app->groupManager, $container);

        $this->injectCacheFactoryToManagers();

        $this->documentManager->inject($this->enumManager);

        $this->documentBulkActionAuthorizator = new DocumentBulkActionAuthorizator($containerConnection, $this->logger, $this->documentManager, $this->documentRepository, $this->app->userManager, $this->groupManager);
        $this->groupStandardOperationsAuthorizator = new GroupStandardOperationsAuthorizator($containerConnection, $this->logger, $this->groupManager);

        $this->injectCacheFactoryToAuthorizators();
    }

    private function initRepositories(DatabaseConnection $db) {
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        $cache = new CacheFactory();
        $cache->setCustomNamespace($this->containerId);

        foreach($rpa as $rp) {
            $rt = $rp->getType();
            $name = $rp->getName();

            if(str_contains($rt, 'Repository')) {
                $className = (string)$rt;

                $this->$name = new $className($db, $this->logger);
                $this->$name->injectCacheFactory($cache);
            }
        }
    }

    private function injectCacheFactoryToManagers() {
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        $cache = new CacheFactory();
        $cache->setCustomNamespace($this->containerId);

        foreach($rpa as $rp) {
            $rt = $rp->getType();
            $name = $rp->getName();

            if(str_contains($rt, 'Manager')) {
                $this->$name->injectCacheFactory($cache);
            }
        }
    }

    private function injectCacheFactoryToAuthorizators() {
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        $cache = new CacheFactory();
        $cache->setCustomNamespace($this->containerId);

        foreach($rpa as $rp) {
            $rt = $rp->getType();
            $name = $rp->getName();

            if(str_contains($rt, 'Authorizator')) {
                $this->$name->injectCacheFactory($cache);
            }
        }
    }
}

?>