<?php

namespace App\Core;

use App\Authenticators\UserAuthenticator;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseManager;
use App\Core\Http\HttpRequest;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\ModuleDoesNotExistException;
use App\Logger\Logger;
use App\Managers\ContainerInviteManager;
use App\Managers\ContainerManager;
use App\Managers\EntityManager;
use App\Managers\GroupManager;
use App\Managers\UserManager;
use App\Modules\ModuleManager;
use App\Repositories\ContainerInviteRepository;
use App\Repositories\ContainerRepository;
use App\Repositories\ContentRepository;
use App\Repositories\GridExportRepository;
use App\Repositories\GroupMembershipRepository;
use App\Repositories\GroupRepository;
use App\Repositories\SystemServicesRepository;
use App\Repositories\TransactionLogRepository;
use App\Repositories\UserRepository;
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

    public ServiceManager $serviceManager;
    public UserManager $userManager;
    public EntityManager $entityManager;
    public GroupManager $groupManager;
    public ContainerManager $containerManager;
    public ContainerInviteManager $containerInviteManager;

    public array $repositories;

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

        $this->initRepositories();

        $this->userAuth = new UserAuthenticator($this->userRepository, $this->logger);

        $this->dbManager = new DatabaseManager($this->db, $this->logger);

        $this->entityManager = new EntityManager($this->logger, $this->contentRepository);
        $this->serviceManager = new ServiceManager($this->systemServicesRepository, $this->userRepository, $this->entityManager);
        $this->userManager = new UserManager($this->logger, $this->userRepository, $this->entityManager);
        $this->groupManager = new GroupManager($this->logger, $this->entityManager, $this->groupRepository, $this->groupMembershipRepository);
        $this->containerManager = new ContainerManager($this->logger, $this->entityManager, $this->containerRepository, $this->dbManager, $this->groupManager);
        $this->containerInviteManager = new ContainerInviteManager($this->logger, $this->entityManager, $this->containerInviteRepository);

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

                $this->$name = new $className($this->db, $this->logger);

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
            $this->currentUser = $this->userRepository->getUserById($_SESSION['userId']);
        } else {
            if((!isset($_GET['page']) || (isset($_GET['page']) && $_GET['page'] != 'Anonym:Logout')) && !isset($_SESSION['is_logging_in']) && !isset($_SESSION['is_registering'])) {
                //$this->redirect(['page' => 'Anonym:Logout', 'action' => 'logout']); // had to be commented because it caused a overflow because of infinite redirects

                if($message != '') {
                    $fmHash = $this->flashMessage($message);
                }
            }
        }

        if(isset($_GET['isAjax']) && $_GET['isAjax'] == '1') {
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
     * @param array $urlParams URL params
     */
    public function redirect(array $urlParams) {
        $url = '';

        if(empty($urlParams)) {
            $url = '?';
        } else {
            $url = $this->composeURL($urlParams);
        }

        header('Location: ' . $url);
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
     * Saves a flash message to persistent cache
     * 
     * @param string $text Flash message text
     * @param string $type Flash message type
     */
    public function flashMessage(string $text, string $type = 'info') {
        $cacheFactory = new CacheFactory();

        if(array_key_exists('container', $_SESSION)) {
            $containerId = $_SESSION['container'];
            $cacheFactory->setCustomNamespace($containerId);
        }

        $cache = $cacheFactory->getCache(CacheNames::FLASH_MESSAGES);

        $hash = HashManager::createHash(8, false);

        $cache->save($hash, function() use ($type, $text, $hash) {
            return [
                [
                    'type' => $type,
                    'text' => $text,
                    'hash' => $hash,
                    'autoClose' => '5'
                ]
            ];
        });

        return $hash;
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
            $fmHash = $this->flashMessage('Container created successfully.');
            $this->refreshPage(['_fm' => $fmHash]);
        }
        $moduleObject->setLogger($this->logger);
        $moduleObject->setHttpRequest($this->getRequest());

        $this->logger->info('Initializing render engine.', __METHOD__);
        $re = new RenderEngine($this->logger, $moduleObject, $this->currentPresenter, $this->currentAction, $this);
        $this->logger->info('Rendering page content.', __METHOD__);
        $re->setAjax($this->isAjaxRequest);
        try {
            return $re->render();
        } catch(AException|Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates a HttpRequest instance that contains all query variables
     * 
     * @return HttpRequest HttpRequest instance
     */
    private function getRequest() {
        $request = new HttpRequest();

        foreach($_GET as $k => $v) {
            if($k == 'isAjax') {
                $request->isAjax = true;
            } else {
                $request->query[$k] = $v;
            }
        }

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

        $isAjax = '0';

        if(isset($_GET['isAjax'])) {
            $isAjax = htmlspecialchars($_GET['isAjax']);
        }

        if ($log) $this->logger->info('Current URL: [module => ' . $this->currentModule . ', presenter => ' . $this->currentPresenter . ', action => ' . $this->currentAction . ', isAjax => ' . $isAjax . ']', __METHOD__);
    }
}

?>