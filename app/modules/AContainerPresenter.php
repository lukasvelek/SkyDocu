<?php

namespace App\Modules;

use App\Authorizators\ContainerProcessAuthorizator;
use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Authorizators\SupervisorAuthorizator;
use App\Constants\SessionNames;
use App\Core\Caching\CacheFactory;
use App\Core\DatabaseConnection;
use App\Entities\ApiTokenEntity;
use App\Managers\Container\ArchiveManager;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\EnumManager;
use App\Managers\Container\ExternalSystemsManager;
use App\Managers\Container\FileStorageManager;
use App\Managers\Container\FolderManager;
use App\Managers\Container\GridManager;
use App\Managers\Container\GroupManager;
use App\Managers\Container\MetadataManager;
use App\Managers\Container\ProcessInstanceManager;
use App\Managers\Container\ProcessManager;
use App\Managers\Container\ProcessMetadataManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ArchiveRepository;
use App\Repositories\Container\DocumentClassRepository;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\Container\ExternalSystemLogRepository;
use App\Repositories\Container\ExternalSystemRightsRepository;
use App\Repositories\Container\ExternalSystemsRepository;
use App\Repositories\Container\ExternalSystemTokenRepository;
use App\Repositories\Container\FileStorageRepository;
use App\Repositories\Container\FolderRepository;
use App\Repositories\Container\GridRepository;
use App\Repositories\Container\GroupRepository;
use App\Repositories\Container\MetadataRepository;
use App\Repositories\Container\ProcessInstanceRepository;
use App\Repositories\Container\ProcessMetadataRepository;
use App\Repositories\Container\ProcessRepository;
use App\Repositories\ContentRepository;
use ReflectionClass;

/**
 * AContainerPresenter is common presenter ancestor for all container presenters. It contains useful variables and methods.
 * 
 * @author Lukas Velek
 */
abstract class AContainerPresenter extends APresenter {
    protected FolderRepository $folderRepository;
    protected GroupRepository $groupRepository;
    protected ContentRepository $contentRepository;
    protected DocumentRepository $documentRepository;
    protected DocumentClassRepository $documentClassRepository;
    protected MetadataRepository $metadataRepository;
    protected GridRepository $gridRepository;
    protected ProcessRepository $processRepository;
    protected ArchiveRepository $archiveRepository;
    protected FileStorageRepository $fileStorageRepository;
    protected ExternalSystemsRepository $externalSystemsRepository;
    protected ExternalSystemLogRepository $externalSystemLogRepository;
    protected ExternalSystemTokenRepository $externalSystemTokenRepository;
    protected ExternalSystemRightsRepository $externalSystemRightsRepository;
    protected ProcessInstanceRepository $processInstanceRepository;
    protected ProcessMetadataRepository $processMetadataRepository;
    
    protected EntityManager $entityManager;
    protected FolderManager $folderManager;
    protected DocumentManager $documentManager;
    protected GroupManager $groupManager;
    protected MetadataManager $metadataManager;
    protected EnumManager $enumManager;
    protected GridManager $gridManager;
    protected ArchiveManager $archiveManager;
    protected FileStorageManager $fileStorageManager;
    protected ExternalSystemsManager $externalSystemsManager;
    protected ProcessManager $processManager;
    protected ProcessInstanceManager $processInstanceManager;
    protected ProcessMetadataManager $processMetadataManager;

    protected GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator;
    protected SupervisorAuthorizator $supervisorAuthorizator;
    protected ContainerProcessAuthorizator $containerProcessAuthorizator;

    protected string $containerId;

    private array $_reflectionParamsCache;
    private ?CacheFactory $containerCacheFactory;

    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->_reflectionParamsCache = [];
        $this->containerCacheFactory = null;
    }

    /**
     * Starts up the container presenter. Here are container repositories and managers and other classes instantiated.
     */
    public function startup() {
        parent::startup();

        $containerId = $this->httpSessionGet(SessionNames::CONTAINER);
        $container = $this->app->containerManager->getContainerById($containerId);
        $containerConnection = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

        $this->containerId = $containerId;

        $this->logger->setContainerId($this->containerId);

        $this->initRepositories($containerConnection);

        $this->entityManager = new EntityManager($this->logger, $this->contentRepository);
        $this->folderManager = new FolderManager($this->logger, $this->entityManager, $this->folderRepository, $this->groupRepository);
        $this->documentManager = new DocumentManager($this->logger, $this->entityManager, $this->documentRepository, $this->documentClassRepository, $this->groupRepository, $this->folderRepository);
        $this->groupManager = new GroupManager($this->logger, $this->entityManager, $this->groupRepository, $this->app->userRepository);
        $this->metadataManager = new MetadataManager($this->logger, $this->entityManager, $this->metadataRepository, $this->folderRepository);
        $this->enumManager = new EnumManager($this->logger, $this->entityManager, $this->app->userRepository, $this->app->groupManager, $container);
        $this->gridManager = new GridManager($this->logger, $this->entityManager, $this->gridRepository);

        $this->initManagers();
        $this->injectCacheFactoryToManagers();

        $this->documentManager->enumManager = $this->enumManager;

        $this->groupStandardOperationsAuthorizator = new GroupStandardOperationsAuthorizator($containerConnection, $this->logger, $this->groupManager);
        $this->supervisorAuthorizator = new SupervisorAuthorizator($containerConnection, $this->logger, $this->groupManager);
        $this->containerProcessAuthorizator = new ContainerProcessAuthorizator($containerConnection, $this->logger, $this->processManager, $this->processInstanceManager, $this->groupManager, $this->app->userManager, $this->app->jobQueueRepository);

        $this->injectCacheFactoryToAuthorizators();

        $this->componentFactory->setCacheFactory($this->getContainerCacheFactory());
    }

    /**
     * Automatically creates instances of managers
     */
    private function initManagers() {
        $managers = [
            'archiveManager' => [
                'archiveRepository'
            ],
            'fileStorageManager' => [
                'fileStorageRepository'
            ],
            'externalSystemsManager' => [
                'externalSystemsRepository',
                'externalSystemLogRepository',
                'externalSystemTokenRepository',
                'externalSystemRightsRepository'
            ],
            'processManager' => [
                'processRepository'
            ],
            'processInstanceManager' => [
                'processInstanceRepository',
                'groupManager',
                ':userManager'
            ],
            'processMetadataManager' => [
                'processMetadataRepository'
            ]
        ];

        $notFound = [];
        foreach($managers as $varName => $args) {
            if(!empty($this->_reflectionParamsCache) && array_key_exists($varName, $this->_reflectionParamsCache)) {
                $class = $this->_reflectionParamsCache[$varName];

                $className = (string)$class;

                $realArgs = [
                    $this->logger,
                    $this->entityManager
                ];
                foreach($args as $arg) {
                    if(str_starts_with($arg, ':')) {
                        $_arg = explode(':', $arg)[1];
                        $realArgs[] = $this->app->$_arg;
                    } else {
                        $realArgs[] = $this->$arg;
                    }
                }

                /**
                 * @var \App\Managers\AManager $obj
                 */
                $obj = new $className(...$realArgs);
                $obj->inject($this->logger, $this->entityManager);
                $this->{$varName} = $obj;
            } else {
                $notFound[] = $varName;
            }
        }

        if(!empty($notFound)) {
            $rc = new ReflectionClass($this);
            $rpa = $rc->getProperties();
            foreach($rpa as $rp) {
                $class = $rp->getType();
                $name = $rp->getName();
                
                if(in_array($name, $notFound)) {
                    $className = (string)$class;

                    /**
                     * @var \App\Managers\AManager $obj
                     */
                    $obj = new $className(...$args);
                    $obj->inject($this->logger, $this->entityManager);
                    $this->{$varName} = $obj;
                    $this->_reflectionParamsCache[$varName] = $class;
                }
            }
        }
    }

    /**
     * Automatically creates instances of repositories
     * 
     * @param DatabaseConnection $db Container DB connection
     */
    private function initRepositories(DatabaseConnection $db) {
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        $cache = $this->getContainerCacheFactory();

        foreach($rpa as $rp) {
            $rt = $rp->getType();
            $name = $rp->getName();

            $this->_reflectionParamsCache[$name] = $rt;

            if(str_contains($rt, 'Repository')) {
                $className = (string)$rt;

                $this->$name = new $className($db, $this->logger, $this->app->transactionLogRepository);
                $this->$name->injectCacheFactory($cache);
                $this->$name->setContainerId($this->containerId);
            }
        }
    }

    /**
     * Injects container CacheFactory instance to managers
     */
    private function injectCacheFactoryToManagers() {
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        $cache = $this->getContainerCacheFactory();

        foreach($rpa as $rp) {
            $rt = $rp->getType();
            $name = $rp->getName();

            if(str_contains($rt, 'Manager')) {
                $this->$name->injectCacheFactory($cache);
            }
        }
    }

    /**
     * Injects container CacheFactory instance to authorizators
     */
    private function injectCacheFactoryToAuthorizators() {
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        $cache = $this->getContainerCacheFactory();

        foreach($rpa as $rp) {
            $rt = $rp->getType();
            $name = $rp->getName();

            if(str_contains($rt, 'Authorizator')) {
                $this->$name->injectCacheFactory($cache);
            }
        }
    }

    /**
     * Gets container CacheFactory instance
     * 
     * @return CacheFactory Container CacheFactory instance
     */
    private function getContainerCacheFactory() {
        if($this->containerCacheFactory === null) {
            $cache = $this->cacheFactory;
            $cache->setCustomNamespace($this->containerId);

            $this->containerCacheFactory = $cache;
        }

        $tmp = &$this->containerCacheFactory;
        return $tmp;
    }

    /**
     * Returns system API token
     */
    protected function getSystemApiToken(): string {
        $system = $this->externalSystemsManager->getSystemExternalSystem();

        $token = $this->externalSystemsManager->createOrGetToken($system->systemId);

        $entity = ApiTokenEntity::createNewEntity($token, $this->containerId, $system->systemId);
        $entity->setUserId($this->getUserId());
        return $entity->convertToToken();
    }
}

?>