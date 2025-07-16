<?php

namespace App\Core;

use App\Authenticators\UserAuthenticator;
use App\Constants\SessionNames;
use App\Core\Caching\CacheFactory;
use App\Core\DB\DatabaseManager;
use App\Core\DB\PeeQL;
use App\Core\Http\HttpRequest;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\ModuleDoesNotExistException;
use App\Logger\Logger;
use App\Managers\ContainerDatabaseManager;
use App\Managers\ContainerInviteManager;
use App\Managers\ContainerManager;
use App\Managers\EntityManager;
use App\Managers\FileStorageManager;
use App\Managers\GroupManager;
use App\Managers\JobQueueManager;
use App\Managers\ProcessManager;
use App\Managers\UserAbsenceManager;
use App\Managers\UserManager;
use App\Managers\UserSubstituteManager;
use App\Modules\ModuleManager;
use App\Repositories\ContainerDatabaseRepository;
use App\Repositories\ContainerInviteRepository;
use App\Repositories\ContainerRepository;
use App\Repositories\ContentRepository;
use App\Repositories\FileStorageRepository;
use App\Repositories\GridExportRepository;
use App\Repositories\GroupMembershipRepository;
use App\Repositories\GroupRepository;
use App\Repositories\JobQueueProcessingHistoryRepository;
use App\Repositories\JobQueueRepository;
use App\Repositories\ProcessRepository;
use App\Repositories\SystemServicesRepository;
use App\Repositories\TransactionLogRepository;
use App\Repositories\UserAbsenceRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserSubstituteRepository;
use App\UI\ExceptionPage\ExceptionPage;
use App\UI\LinkBuilder;
use Exception;
use ReflectionClass;

/**
 * Application class that contains all objects and useful functions.
 * It is also the starting point of the application.
 * 
 * @author Lukas Velek
 */
class Application {
    public const APP_VERSION = '1.3-dev';
    public const APP_VERSION_RELEASE_DATE = '-';

    private array $modules;
    public ?UserEntity $currentUser;

    private ?string $currentModule;
    private ?string $currentPresenter;
    private ?string $currentAction;

    private bool $isAjaxRequest;

    private ModuleManager $moduleManager;
    public Logger $logger;
    private DatabaseConnection $db;
    public DatabaseManager $dbManager;

    public UserAuthenticator $userAuth;

    public UserRepository $userRepository;
    public SystemServicesRepository $systemServicesRepository;
    public TransactionLogRepository $transactionLogRepository;
    public ContentRepository $contentRepository;
    public GridExportRepository $gridExportRepository;
    public GroupRepository $groupRepository;
    public GroupMembershipRepository $groupMembershipRepository;
    public ContainerRepository $containerRepository;
    public ContainerInviteRepository $containerInviteRepository;
    public UserAbsenceRepository $userAbsenceRepository;
    public UserSubstituteRepository $userSubstituteRepository;
    public ContainerDatabaseRepository $containerDatabaseRepository;
    public ProcessRepository $processRepository;
    public JobQueueRepository $jobQueueRepository;
    public JobQueueProcessingHistoryRepository $jobQueueProcessingHistoryRepository;
    public FileStorageRepository $fileStorageRepository;

    public ServiceManager $serviceManager;
    public UserManager $userManager;
    public EntityManager $entityManager;
    public GroupManager $groupManager;
    public ContainerManager $containerManager;
    public ContainerInviteManager $containerInviteManager;
    public UserAbsenceManager $userAbsenceManager;
    public UserSubstituteManager $userSubstituteManager;
    public ContainerDatabaseManager $containerDatabaseManager;
    public ProcessManager $processManager;
    public JobQueueManager $jobQueueManager;
    public FileStorageManager $fileStorageManager;

    public array $repositories;

    public CacheFactory $cacheFactory;

    public PeeQL $peeql;

    /**
     * The Application constructor. It creates objects of all used classes.
     */
    public function __construct() {
        $this->modules = [];
        $this->currentModule = null;
        $this->currentPresenter = null;
        $this->currentAction = null;
        
        $this->currentUser = null;

        $this->moduleManager = new ModuleManager();

        $this->logger = new Logger();
        $this->logger->info('Logger initialized.', __METHOD__);
        try {
            $this->db = new DatabaseConnection(DB_MASTER_NAME);
        } catch(AException $e) {
            throw $e;
        }
        $this->logger->info('Database connection established', __METHOD__);

        $this->cacheFactory = new CacheFactory();

        $this->transactionLogRepository = new TransactionLogRepository($this->db, $this->logger);

        $this->initRepositories();

        $this->userAuth = new UserAuthenticator($this->userRepository, $this->logger);

        $this->dbManager = new DatabaseManager($this->db, $this->logger);

        $this->entityManager = new EntityManager($this->logger, $this->contentRepository);
        $this->serviceManager = new ServiceManager($this->systemServicesRepository, $this->userRepository, $this->entityManager);
        $this->userManager = new UserManager($this->logger, $this->userRepository, $this->entityManager);
        $this->groupManager = new GroupManager($this->logger, $this->entityManager, $this->groupRepository, $this->groupMembershipRepository);
        $this->containerDatabaseManager = new ContainerDatabaseManager($this->logger, $this->entityManager, $this->containerDatabaseRepository, $this->dbManager);
        $this->containerManager = new ContainerManager($this->logger, $this->entityManager, $this->containerRepository, $this->dbManager, $this->groupManager, $this->db, $this->containerDatabaseManager);
        $this->containerInviteManager = new ContainerInviteManager($this->logger, $this->entityManager, $this->containerInviteRepository);
        $this->userAbsenceManager = new UserAbsenceManager($this->logger, $this->entityManager, $this->userAbsenceRepository);
        $this->userSubstituteManager = new UserSubstituteManager($this->logger, $this->entityManager, $this->userSubstituteRepository);
        $this->processManager = new ProcessManager($this->logger, $this->entityManager, $this->processRepository);
        $this->jobQueueManager = new JobQueueManager($this->logger, $this->entityManager, $this->jobQueueRepository, $this->jobQueueProcessingHistoryRepository);
        $this->fileStorageManager = new FileStorageManager($this->logger, $this->entityManager, $this->fileStorageRepository);

        $this->initManagers();

        $this->isAjaxRequest = false;

        $this->loadModules();
        
        if(!FileManager::fileExists(__DIR__ . '\\install')) {
            try {
                // Installer will now install the application
                $installer = new Installer($this->db);
                $installer->install();
            } catch(AException $e) {
                throw new GeneralException('Could not install database. Reason: ' . $e->getMessage(), $e);
            }
        }

        $this->peeql = new PeeQL($this->db, $this->logger, $this->transactionLogRepository);
    }

    /**
     * Initializes *Manager classes
     */
    private function initManagers() {
        foreach([
            $this->entityManager,
            $this->userManager,
            $this->groupManager,
            $this->containerDatabaseManager,
            $this->containerManager,
            $this->containerInviteManager,
            $this->userAbsenceManager,
            $this->userSubstituteManager,
            $this->processManager,
            $this->jobQueueManager
        ] as $manager) {
            $manager->injectCacheFactory($this->cacheFactory);
        }
    }

    /**
     * Initializes *Repository classes
     */
    private function initRepositories() {
        $this->repositories = [];

        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        foreach($rpa as $rp) {
            $rt = $rp->getType();

            if(str_contains($rt, 'Repository')) {
                $name = $rp->getName();
                $className = (string)$rt;

                if($name == 'transactionLogRepository') continue;

                $this->$name = new $className($this->db, $this->logger, $this->transactionLogRepository);
                
                if(method_exists($this->$name, 'injectCacheFactory')) {
                    $this->$name->injectCacheFactory($this->cacheFactory);
                }

                $this->repositories[$name] = $this->$name;
            }
        }
    }
    
    /**
     * The point where all the operations are called from.
     * It tries to authenticate the current user and then calls a render method.
     */
    public function run() {
        $this->getCurrentModulePresenterAction();

        $message = '';
        if($this->userAuth->fastAuthUser($message)) {
            // login
            $this->currentUser = $this->userRepository->getUserById($_SESSION[SessionNames::USER_ID]);
        } else {
            if((!isset($_GET['page']) || (isset($_GET['page']) && !in_array($_GET['page'], [
                'Anonym:Logout',
                'Anonym:AutoLogin'
            ]))) && !isset($_SESSION[SessionNames::IS_LOGGING_IN]) && !isset($_SESSION[SessionNames::IS_REGISTERING])) {
                $this->redirect(['page' => 'Anonym:Logout', 'action' => 'logout', 'reason' => 'authenticationError']);
            }
        }

        /**
         * Instead of query parameter isAjax, it can be easily determined with the request header.
         */
        if(array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            $this->isAjaxRequest = true;
        }

        try {
            echo $this->render();
        } catch(AException|Exception $e) {
            throw $e;
        }
    }

    /**
     * Redirects current page to other page using header('Location: ') method.
     * 
     * @param array|string $urlParams URL params or full URL
     */
    public function redirect(array|string $urlParams) {
        $url = '';
        if(is_array($urlParams)) {
            if(empty($urlParams)) {
                $url = '?';
            } else {
                $url = $this->composeURL($urlParams);
            }
        } else {
            $url = $urlParams;
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Forces file download
     * 
     * @param string $filepath File path
     */
    public function forceDownloadFile(string $filepath) {
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: Binary');
        header('Content-disposition: attachment; filename="' . basename($filepath . '"'));
        readfile($filepath);
        exit;
    }

    /**
     * Refreshes current page with the same parameters
     * 
     * @param array $customParams Additional parameters
     */
    private function refreshPage(array $customParams = []) {
        $request = $this->getRequest()->query;

        $params = [];
        foreach($request as $key => $value) {
            $params[$key] = $value;
        }

        foreach($customParams as $key => $value) {
            $params[$key] = $value;
        }

        $this->redirect($params);
    }
    
    /**
     * Creates a single line URL from a URL params array
     * 
     * @param array $param URL params
     * @return string URL
     */
    public function composeURL(array $params) {
        return LinkBuilder::convertUrlArrayToString($params);
    }
    
    /**
     * Returns the rendered page content
     * 
     * First it creates a module instance, then it creates a RenderEngine instance and call it's render function.
     * 
     * @return string Page HTML content
     */
    private function render() {
        if(!in_array($this->currentModule, $this->modules)) {
            throw new ModuleDoesNotExistException($this->currentModule);
        }

        $this->logger->info('Creating module.', __METHOD__);
        try {
            $moduleObject = $this->moduleManager->createModule($this->currentModule);
        } catch(Exception $e) {
            $this->refreshPage();
        }
        $moduleObject->setLogger($this->logger);
        $moduleObject->setHttpRequest($this->getRequest());
        $moduleObject->setCacheFactory($this->cacheFactory);

        $this->logger->info('Initializing render engine.', __METHOD__);
        $re = new RenderEngine($this->logger, $moduleObject, $this->currentPresenter, $this->currentAction, $this);
        $this->logger->info('Rendering page content.', __METHOD__);
        $re->setAjax($this->isAjaxRequest);
        try {
            return $re->render();
        } catch(AException|Exception $e) {
            try {
                $ep = new ExceptionPage($this, $this->getRequest());
                $ep->setException($e);
                return $ep->render();
            } catch(Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * Creates a HttpRequest instance that contains all query variables
     * 
     * @return HttpRequest HttpRequest instance
     */
    public function getRequest() {
        $request = new HttpRequest();

        foreach($_GET as $k => $v) {
            $request->query[$k] = $v;
        }

        $request->isAjax = $this->isAjaxRequest;

        if(!empty($_POST)) {
            foreach($_POST as $k => $v) {
                $request->post[$k] = $v;
            }
        }

        $request->currentUser = $this->currentUser;
        $request->method = $_SERVER['REQUEST_METHOD'];

        return $request;
    }

    /**
     * Loads modules
     */
    private function loadModules() {
        $this->logger->info('Loading modules.', __METHOD__);
        $this->modules = $this->moduleManager->loadModules();
    }

    /**
     * Returns the current module, presenter and action from URL
     */
    private function getCurrentModulePresenterAction(bool $log = true) {
        $page = htmlspecialchars($_GET['page']);

        $pageParts = explode(':', $page);

        $this->currentModule = $pageParts[0] . 'Module';
        $this->currentPresenter = $pageParts[1] . 'Presenter';

        if(isset($_GET['action'])) {
            $this->currentAction = htmlspecialchars($_GET['action']);
        } else {
            $this->currentAction = 'default';
        }

        if ($log) $this->logger->info('Current URL: [module => ' . $this->currentModule . ', presenter => ' . $this->currentPresenter . ', action => ' . $this->currentAction . ']', __METHOD__);
        
        $params = [];
        foreach($_GET as $k => $v) {
            if(in_array($k, [
                'page',
                'action'
            ])) continue;

            $params[] = sprintf('%s => %s', $k, $v);
        }

        if ($log) $this->logger->info('Current URL parameters: [' . implode(', ', $params) . ']', __METHOD__);
    }
}

?>