<?php

namespace App\Core;

use App\Authenticators\UserAuthenticator;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Core\Http\HttpRequest;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\ModuleDoesNotExistException;
use App\Helpers\LinkHelper;
use App\Logger\Logger;
use App\Managers\EntityManager;
use App\Managers\UserManager;
use App\Modules\ModuleManager;
use App\Repositories\ContentRepository;
use App\Repositories\GridExportRepository;
use App\Repositories\SystemServicesRepository;
use App\Repositories\SystemStatusRepository;
use App\Repositories\TransactionLogRepository;
use App\Repositories\UserRepository;
use Exception;
use ReflectionClass;

/**
 * Application class that contains all objects and useful functions.
 * It is also the starting point of all the application's behavior.
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

    public UserAuthenticator $userAuth;

    public UserRepository $userRepository;
    public SystemStatusRepository $systemStatusRepository;
    public SystemServicesRepository $systemServicesRepository;
    public TransactionLogRepository $transactionLogRepository;
    public ContentRepository $contentRepository;
    public GridExportRepository $gridExportRepository;

    public ServiceManager $serviceManager;
    public UserManager $userManager;
    public EntityManager $entityManager;

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

        $this->entityManager = new EntityManager($this->logger, $this->contentRepository);
        $this->serviceManager = new ServiceManager($this->systemServicesRepository);
        $this->userManager = new UserManager($this->logger, $this->userRepository, $this->entityManager);

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
        $rc = new ReflectionClass($this);

        $rpa = $rc->getProperties();

        foreach($rpa as $rp) {
            $rt = $rp->getType();

            if(str_contains($rt, 'Repository')) {
                $name = $rp->getName();
                $className = (string)$rt;

                $this->$name = new $className($this->db, $this->logger);
            }
        }
    }

    /**
     * Used for old AJAX functions. It has become deprecated when AJAX functionality was implemented into presenters.
     * 
     * @param string $currentUserId Current user's ID
     * 
     * @deprecated
     */
    public function ajaxRun(string $currentUserId) {
        $this->currentUser = $this->userRepository->getUserById($currentUserId);
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
            if((!isset($_GET['page']) || (isset($_GET['page']) && $_GET['page'] != 'AnonymModule:Logout')) && !isset($_SESSION['is_logging_in'])) {
                $this->redirect(['page' => 'AnonymModule:Logout', 'action' => 'logout']);

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
     * Creates a single line URL from a URL params array
     * 
     * @param array $param URL params
     * @return string URL
     */
    public function composeURL(array $params) {
        return LinkHelper::createUrlFromArray($params);
    }

    /**
     * Saves a flash message to persistent cache
     * 
     * @param string $text Flash message text
     * @param string $type Flash message type
     */
    public function flashMessage(string $text, string $type = 'info') {
        $cacheFactory = new CacheFactory();
        $cache = $cacheFactory->getCache(CacheNames::FLASH_MESSAGES);

        $hash = HashManager::createHash(8, false);

        $cache->save($hash, function() use ($type, $text) {
            return ['type' => $type, 'text' => $text];
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
        $moduleObject = $this->moduleManager->createModule($this->currentModule);
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
    private function getCurrentModulePresenterAction() {
        $page = htmlspecialchars($_GET['page']);

        $pageParts = explode(':', $page);

        $this->currentModule = $pageParts[0];
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

        $this->logger->info('Current URL: [module => ' . $this->currentModule . ', presenter => ' . $this->currentPresenter . ', action => ' . $this->currentAction . ', isAjax => ' . $isAjax . ']', __METHOD__);
    }
}

?>