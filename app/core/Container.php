<?php

namespace App\Core;

use App\Authorizators\DocumentBulkActionAuthorizator;
use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Authorizators\SupervisorAuthorizator;
use App\Core\Caching\CacheFactory;
use App\Entities\ContainerEntity;
use App\Lib\Processes\ProcessFactory;
use App\Logger\Logger;
use App\Managers\Container\ArchiveManager;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\EnumManager;
use App\Managers\Container\ExternalSystemsManager;
use App\Managers\Container\FileStorageManager;
use App\Managers\Container\FolderManager;
use App\Managers\Container\GridManager;
use App\Managers\Container\GroupManager;
use App\Managers\Container\MetadataManager;
use App\Managers\Container\ProcessManager;
use App\Managers\Container\StandaloneProcessManager;
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
use App\Repositories\Container\ProcessRepository;
use App\Repositories\Container\TransactionLogRepository;
use App\Repositories\ContentRepository;
use ReflectionClass;

/**
 * Container contains all useful repositories, managers and authorizators for containers
 * 
 * @author Lukas Velek
 */
class Container {
    private Application $app;
    private CacheFactory $cacheFactory;
    private Logger $logger;

    private array $_reflectionParamsCache;

    public FolderRepository $folderRepository;
    public GroupRepository $groupRepository;
    public ContentRepository $contentRepository;
    public DocumentRepository $documentRepository;
    public DocumentClassRepository $documentClassRepository;
    public MetadataRepository $metadataRepository;
    public GridRepository $gridRepository;
    public ProcessRepository $processRepository;
    public ArchiveRepository $archiveRepository;
    public FileStorageRepository $fileStorageRepository;
    public ExternalSystemsRepository $externalSystemsRepository;
    public ExternalSystemLogRepository $externalSystemLogRepository;
    public ExternalSystemTokenRepository $externalSystemTokenRepository;
    public ExternalSystemRightsRepository $externalSystemRightsRepository;
    
    public EntityManager $entityManager;
    public FolderManager $folderManager;
    public DocumentManager $documentManager;
    public GroupManager $groupManager;
    public MetadataManager $metadataManager;
    public EnumManager $enumManager;
    public GridManager $gridManager;
    public ProcessManager $processManager;
    public StandaloneProcessManager $standaloneProcessManager;
    public ArchiveManager $archiveManager;
    public FileStorageManager $fileStorageManager;
    public ExternalSystemsManager $externalSystemsManager;

    public DocumentBulkActionAuthorizator $documentBulkActionAuthorizator;
    public GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator;
    public SupervisorAuthorizator $supervisorAuthorizator;

    public ProcessFactory $processFactory;

    public string $containerId;
    public ContainerEntity $container;
    public DatabaseConnection $conn;

    /**
     * Class constructor
     * 
     * @param Application $app Application instance
     * @param string $containerId Container ID
     */
    public function __construct(Application $app, string $containerId) {
        $this->app = $app;
        $this->containerId = $containerId;

        $this->container = $this->app->containerManager->getContainerById($this->containerId);
        $this->conn = $this->app->dbManager->getConnectionToDatabase($this->container->getDefaultDatabase()->getName());

        $cache = $this->app->cacheFactory;
        $cache->setCustomNamespace($this->containerId);
        $this->cacheFactory = $cache;

        $this->logger = new Logger();
        $this->logger->setContainerId($this->containerId);

        $this->_reflectionParamsCache = [];

        $this->initRepositories();

        $this->entityManager = new EntityManager($this->logger, $this->contentRepository);
        $this->folderManager = new FolderManager($this->logger, $this->entityManager, $this->folderRepository, $this->groupRepository);
        $this->documentManager = new DocumentManager($this->logger, $this->entityManager, $this->documentRepository, $this->documentClassRepository, $this->groupRepository, $this->folderRepository);
        $this->groupManager = new GroupManager($this->logger, $this->entityManager, $this->groupRepository, $this->app->userRepository);
        $this->metadataManager = new MetadataManager($this->logger, $this->entityManager, $this->metadataRepository, $this->folderRepository);
        $this->enumManager = new EnumManager($this->logger, $this->entityManager, $this->app->userRepository, $this->app->groupManager, $this->container);
        $this->gridManager = new GridManager($this->logger, $this->entityManager, $this->gridRepository);

        $this->initManagers();
        $this->injectCacheFactoryToManagers();

        $this->enumManager->standaloneProcessManager = $this->standaloneProcessManager;
        $this->documentManager->enumManager = $this->enumManager;
        
        $this->documentBulkActionAuthorizator = new DocumentBulkActionAuthorizator($this->conn, $this->logger, $this->documentManager, $this->documentRepository, $this->app->userManager, $this->groupManager, $this->processManager, $this->archiveManager, $this->folderManager);
        $this->groupStandardOperationsAuthorizator = new GroupStandardOperationsAuthorizator($this->conn, $this->logger, $this->groupManager);
        $this->supervisorAuthorizator = new SupervisorAuthorizator($this->conn, $this->logger, $this->groupManager);

        $this->injectCacheFactoryToAuthorizators();

        $user = $this->app->userManager->getServiceUserId();
        $serviceUser = $this->app->userManager->getUserById($user);

        $this->processFactory = new ProcessFactory(
            $this->documentManager,
            $this->groupStandardOperationsAuthorizator,
            $this->documentBulkActionAuthorizator,
            $this->app->userManager,
            $this->groupManager,
            $serviceUser,
            $this->containerId,
            $this->processManager,
            $this->archiveManager
        );
    }

    /**
     * Automatically creates instances of repositories
     */
    private function initRepositories() {
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        foreach($rpa as $rp) {
            $rt = $rp->getType();
            $name = $rp->getName();

            $this->_reflectionParamsCache[$name] = $rt;

            if(str_contains($rt, 'Repository')) {
                $className = (string)$rt;

                $this->$name = new $className($this->conn, $this->logger, $this->app->transactionLogRepository);
                $this->$name->injectCacheFactory($this->cacheFactory);
                $this->$name->setContainerId($this->containerId);
            }
        }
    }

    /**
     * Automatically creates instances of managers
     */
    private function initManagers() {
        $managers = [
            'processManager' => [
                'processRepository',
                'groupManager',
                ':userSubstituteManager',
                ':userAbsenceManager'
            ],
            'archiveManager' => [
                'archiveRepository'
            ],
            'fileStorageManager' => [
                'fileStorageRepository'
            ],
            'standaloneProcessManager' => [
                'processManager',
                ':currentUser',
                ':userManager',
                'documentManager',
                'fileStorageManager',
                'groupManager',
                'folderManager'
            ],
            'externalSystemsManager' => [
                'externalSystemsRepository',
                'externalSystemLogRepository',
                'externalSystemTokenRepository',
                'externalSystemRightsRepository'
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
                    if($arg == ':currentUser') {
                        $user = $this->app->userManager->getServiceUserId();
                        $realArgs[] = $this->app->userManager->getUserById($user);
                        continue;
                    }

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
     * Injects container CacheFactory instance to managers
     */
    private function injectCacheFactoryToManagers() {
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        foreach($rpa as $rp) {
            $rt = $rp->getType();
            $name = $rp->getName();

            if(str_contains($rt, 'Manager')) {
                $this->$name->injectCacheFactory($this->cacheFactory);
            }
        }
    }

    /**
     * Injects container CacheFactory instance to authorizators
     */
    private function injectCacheFactoryToAuthorizators() {
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        foreach($rpa as $rp) {
            $rt = $rp->getType();
            $name = $rp->getName();

            if(str_contains($rt, 'Authorizator')) {
                $this->$name->injectCacheFactory($this->cacheFactory);
            }
        }
    }
}

?>