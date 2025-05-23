<?php

namespace App\Modules;

use App\Constants\AppDesignThemes;
use App\Constants\SessionNames;
use App\Core\AjaxRequestBuilder;
use App\Core\Application;
use App\Core\Caching\CacheFactory;
use App\Core\Configuration;
use App\Core\Datatypes\ArrayList;
use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Core\Http\Ajax\Requests\AAjaxRequest;
use App\Core\Http\AResponse;
use App\Core\Http\JsonErrorResponse;
use App\Core\Router;
use App\Entities\UserEntity;
use App\Exceptions\ActionDoesNotExistException;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NoAjaxResponseException;
use App\Exceptions\TemplateDoesNotExistException;
use App\Logger\Logger;
use App\UI\LinkBuilder;
use Exception;

/**
 * Common presenter class that all presenters must extend. It contains useful methods and most importantly rendering functionality.
 * 
 * @author Lukas Velek
 */
abstract class APresenter extends AGUICore {
    private array $params;
    public string $name;
    private string $title;
    private ?string $tabTitle;
    private ?string $action;
    private ArrayList $presenterCache;
    public ArrayList $scripts;
    private ?string $defaultAction;
    public ?string $moduleName;
    private bool $isAjax;
    private bool $lock;
    private ?UserEntity $currentUser;

    public ?TemplateObject $template;
    public ?TemplateObject $sysTemplate;
    public ?Logger $logger;

    private ArrayList $beforeRenderCallbacks;
    private ArrayList $afterRenderCallbacks;

    public ?CacheFactory $cacheFactory;

    private array $flashMessages;
    private array $specialRedirectUrlParams;
    private bool $isComponentAjax;
    private array $permanentFlashMessages;
    
    public array $components;

    protected ComponentFactory $componentFactory;

    protected Router $router;

    /**
     * The class constructor
     * 
     * @param string $name Presenter name (the class name)
     * @param string $title Presenter title (the friendly name)
     */
    protected function __construct(string $name, string $title) {
        $this->title = $title;
        $this->name = $name;
        $this->params = [];
        $this->action = null;
        $this->template = null;
        $this->sysTemplate = null;
        $this->logger = null;
        $this->defaultAction = null;
        $this->moduleName = null;
        $this->isAjax = false;
        $this->lock = false;
        $this->currentUser = null;
        $this->isComponentAjax = false;
        $this->tabTitle = null;

        $this->presenterCache = new ArrayList();
        $this->presenterCache->setStringKeyType();
        $this->presenterCache->setEnsureKeyType(true);

        $this->scripts = new ArrayList();
        $this->beforeRenderCallbacks = new ArrayList();
        $this->afterRenderCallbacks = new ArrayList();

        $this->cacheFactory = null;

        $this->flashMessages = [];
        $this->specialRedirectUrlParams = [];
        $this->permanentFlashMessages = [];

        $this->components = [];

        $this->router = new Router();
    }

    /**
     * Everything in startup() method is called after an instance of Presenter has been created and before other functionality-handling methods are called.
     */
    public function startup() {
        $this->componentFactory = new ComponentFactory($this->httpRequest, $this, $this->app);
        $this->componentFactory->setCacheFactory($this->cacheFactory);
        $this->router->inject($this, new ModuleManager());
    }

    /**
     * Sets CacheFactory instance
     * 
     * @param CacheFactory $cacheFactory CacheFactory instance
     */
    public function setCacheFactory(CacheFactory $cacheFactory) {
        $this->cacheFactory = $cacheFactory;
    }

    /**
     * Returns current user's ID or null if no user is set
     * 
     * @return string|null Current user's ID or null if no user is set
     */
    public function getUserId() {
        return $this->currentUser?->getId();
    }

    /**
     * Returns current user's UserEntity instance or null if no user is set
     * 
     * @return UserEntity|null Current user's UserEntity instance or null if no user is set
     */
    public function getUser() {
        return $this->currentUser;
    }

    /**
     * Sets variables from Application instance
     */
    private function procesApplicationSet() {
        if($this->app->currentUser !== null) {
            $this->currentUser = $this->app->currentUser;
        }
    }

    /**
     * Sets Application instance
     * 
     * @param Application $app Application instance
     */
    public function setApplication(Application $app) {
        parent::setApplication($app);

        $this->procesApplicationSet();
    }

    /**
     * Locks important variables so they are readonly
     */
    public function lock() {
        $this->lock = true;
    }

    /**
     * Unlocks important variables so they are not readonly
     */
    public function unlock() {
        $this->lock = false;
    }

    /**
     * Returns if the call comes from AJAX
     * 
     * @return bool Is AJAX?
     */
    protected function isAjax() {
        return $this->isAjax;
    }

    /**
     * Sets if the call comes from AJAX
     * 
     * @param bool $isAjax Is AJAX?
     */
    public function setIsAjax(bool $isAjax) {
        if(!$this->lock) {
            $this->isAjax = $isAjax;
        }
    }

    /**
     * Returns a URL with parameters saved in the presenter class as a string (e.g. "?page=UserModule:Users&action=profile&userId=...")
     * 
     * @param string $action Action name
     * @param array $params Custom URL params
     * @return string URL as string
     */
    public function createURLString(string $action, array $params = []) {
        return LinkBuilder::convertUrlArrayToString($this->createURL($action, $params));
    }

    /**
     * Returns a URL with parameters saved in the presenter class
     * 
     * @param string $action Action name
     * @param array $params Custom URL params
     * @param bool $throwException Throw exception
     * @return array URL
     */
    public function createURL(string $action, array $params = [], bool $throwException = true) {
        $module = $this->moduleName;
        $presenter = $this->getCleanName();

        $url = ['page' => $module . ':' . $presenter, 'action' => $action];

        $result = array_merge($url, $params);

        if(Configuration::getAppBranch() == 'TEST') {
            $this->router->checkEndpointExists($result, $throwException);
        }

        return $result;
    }

    /**
     * Returns cleaned version of the presenter's name
     * 
     * Clean means that it does not contain the word "Presenter" at the end
     * 
     * @return string Clean name or name itself
     */
    public function getCleanName() {
        if(str_contains($this->name, 'Presenter')) {
            return substr($this->name, 0, -9);
        } else {
            return $this->name;
        }
    }

    /**
     * Sets the default action name
     * 
     * @param string $actionName Default action name
     */
    public function setDefaultAction(string $actionName) {
        $this->defaultAction = $actionName;
    }

    /**
     * Sets the logger instance to be used for CacheManager
     * 
     * @param Logger $logger Logger instance
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Creates a custom flash message but instead of saving it to cache, it returns its HTML code.
     * 
     * @param string $type Flash message type
     * @param string $text Flash message text
     * @return string HTML code
     */
    protected function createCustomFlashMessage(string $type, string $text) {
        return $this->createFlashMessage($type, $text, 0, true);
    }

    /**
     * Saves data to the "presenter cache" that is temporary. It is used when passing data from handleX() method to renderX() method.
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @deprecated
     */
    protected function saveToPresenterCache(string $key, mixed $value) {
        $this->presenterCache->set($key, $value);
    }

    /**
     * Returns data from the "presenter cache". If no data with given key is found that it returns null.
     * 
     * @param string $key Data key
     * @return mixed Data value or null
     * @deprecated
     */
    protected function loadFromPresenterCache(string $key) {
        return $this->presenterCache->get($key);
    }

    /**
     * Saves a flash message to the current session. Flash messages are automatically closed.
     * 
     * @param string $text Flash message text
     * @param string $type Flash message type
     */
    protected function flashMessage(string $text, string $type = 'info', int $autoCloseLengthInSeconds = 5) {
        if(empty($this->flashMessages)) {
            $hash = HashManager::createHash(8, false);
        } else {
            $hash = $this->flashMessages[0]['hash'];
        }

        $this->flashMessages[] = ['type' => $type, 'text' => $text, 'hash' => $hash, 'autoClose' => $autoCloseLengthInSeconds];
    }

    /**
     * Saves a permanent flash message to presenter cache. Permanent flash messages are not automatically closed.
     * 
     * @param string $text Flash message text
     * @param string $type Flash message type
     */
    protected function permanentFlashMessage(string $text, string $type = 'info') {
        $this->permanentFlashMessages[] = $this->createFlashMessage($type, $text, count($this->permanentFlashMessages), false, true);
    }

    /**
     * Returns HTML code of all permanent flash messages
     * 
     * @return string HTML code of all permanent flash messages
     */
    public function fillPermanentFlashMessages() {
        if(!empty($this->permanentFlashMessages)) {
            return implode('<br>', $this->permanentFlashMessages);
        } else {
            return '';
        }
    }

    /**
     * Redirects the current page to other page. If no parameters are provided then it just refreshes the current page.
     * 
     * @param array|string $url URL params or full URL
     */
    public function redirect(array|string $url) {
        if(!empty($url) && is_array($url)) {    
            if(!array_key_exists('page', $url)) {
                $url['page'] = $this->httpRequest->get('page');
            }

            if(!empty($this->specialRedirectUrlParams)) {
                $url = array_merge($url, $this->specialRedirectUrlParams);
            }

            $this->saveFlashMessagesToCache();
        }

        $this->app->redirect($url);
    }

    /**
     * Sets system parameters in the presenter
     * 
     * @param array $params
     */
    public function setParams(array $params) {
        $this->params = $params;
    }

    /**
     * Creates content template and calls the render<View>() action to fill the template attributes
     * 
     * @param string $moduleName Module name
     */
    protected function createContentTemplate(string $moduleName) {
        try {
            $this->template = $this->beforeRender($moduleName);
        } catch(AException|Exception $e) {
            throw $e;
        }

        if(!$this->isAjax) {
            $renderAction = 'render' . ucfirst($this->action);

            if(method_exists($this, $renderAction)) {
                $this->logger->stopwatch(function() use ($renderAction) {
                    return $this->$renderAction();
                }, 'App\\Modules\\' . $moduleName . '\\' . $this->title . '::' . $renderAction);

                /**
                 * Flash messages are loaded from cache and displayed only if the presenter is really rendered.
                 * Originally the flash messages were loaded automatically. Because of that flash messages couldn't be displayed after several redirections.
                 */
                $this->module->loadFlashMessagesFromCache();
            }
        }
    }

    /**
     * Renders the presenter. It runs operations before the rendering itself, then renders the template and finally performs operations after the rendering.
     * 
     * Here are also the macros of the common template filled.
     * 
     * @param string $moduleName Name of the current module
     */
    public function render(string $moduleName): ?TemplateObject {
        $this->createContentTemplate($moduleName);

        if(!$this->isAjax) {
            $this->fillSystemAttributesToTemplate();
        }
        
        $this->afterRender();

        return $this->template;
    }

    /**
     * Fills the template with system attributes
     */
    private function fillSystemAttributesToTemplate() {
        $date = new DateTime();
        $date->format('Y');
        $date = $date->getResult();

        if($this->sysTemplate !== null) {
            $this->sysTemplate->sys_page_title = $this->getTabTitle();
            $this->sysTemplate->sys_app_name = 'SkyDocu';
            $this->sysTemplate->sys_copyright = (($date > 2024) ? ('2024-' . $date) : ($date));
            $this->sysTemplate->sys_scripts = $this->scripts->getAll();
        
            if($this->currentUser !== null) {
                $this->sysTemplate->sys_user_id = $this->currentUser->getId();
                $this->sysTemplate->sys_design_theme_filename = AppDesignThemes::convertToStyleFileName($this->currentUser->getAppDesignTheme());
            } else {
                $this->sysTemplate->sys_user_id = '';
                $this->sysTemplate->sys_design_theme_filename = AppDesignThemes::convertToStyleFileName(AppDesignThemes::LIGHT);
            }
        }
    }

    /**
     * Returns the tab's title
     * 
     * @return string Tab title
     */
    private function getTabTitle() {
        return $this->tabTitle ?? $this->title;
    }

    /**
     * Sets the tab's title
     * 
     * @param string $title Tab's title
     */
    protected function setTitle(string $title) {
        $this->tabTitle = $title;
    }

    /**
     * Adds a callback that is called before the presenter is rendered.
     * 
     * @param callable $function Callback
     */
    public function addBeforeRenderCallback(callable $function) {
        $this->beforeRenderCallbacks->add(null, $function);
    }

    /**
     * Adds a callback that is called after the presenter is rendered.
     * 
     * @param callable $function Callback
     */
    public function addAfterRenderCallback(callable $function) {
        $this->afterRenderCallbacks->add(null, $function);
    }

    /**
     * Sets the action that the presenter will perform
     * 
     * @param string $title Action name
     */
    public function setAction(string $title) {
        $this->action = $title;
    }

    /**
     * Returns the current action that the presenter will perform
     * 
     * @return string Action name
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * Sets the page content template
     * 
     * @param null|TemplateObject $template Template or null
     */
    public function setTemplate(?TemplateObject $template) {
        $this->sysTemplate = $template;
    }

    /**
     * This method performs all necessary operations before the presenter content is rendered.
     * E.g. it calls the 'handleX()' operation that might not need to be rendered.
     * 
     * @param string $moduleName the module name
     * @return null|TemplateObject Template content or null
     */
    private function beforeRender(string $moduleName) {
        $ok = false;
        $templateContent = null;

        // If the call is AJAX or component AJAX, only action<Name>() has to be handled
        if($this->isAjax && !$this->isComponentAjax) {
            $result = $this->processAction($moduleName);
            if($result !== null) {
                return $result;
            }
        }
        
        // Process handle<Name>()
        $handleAction = 'handle' . ucfirst($this->action);
        if(method_exists($this, $handleAction)) {
            $this->processHandle($moduleName, $ok);
        }

        // Process render<Name>()
        $renderAction = 'render' . ucfirst($this->action);
        if(method_exists($this, $renderAction)) {
            $templateContent = $this->processRender($ok);
        }

        // There has been an error during action handling or rendering
        if($ok === false) {
            if($this->isAjax && !$this->isComponentAjax) {
                return new TemplateObject((new JsonErrorResponse('ActionDoesNotExist'))->getResult());
            } else {
                if($this->defaultAction !== null) {
                    $this->redirect(['page' => $moduleName . ':' . $this->title, 'action' => $this->defaultAction]);
                }

                $this->redirect(['page' => 'Error:E404', 'reason' => 'ActionDoesNotExist', 'calledAction' => $this->action, 'calledPage' => $moduleName . ':' . substr($this->name, 0, -strlen('Presenter'))]);
            }
        }

        // Process component action
        if($this->httpRequest->get('do') !== null) {
            $templateContent2 = $this->processComponentAction($templateContent);

            if($templateContent2 !== null) {
                return $templateContent2;
            }
        }

        $this->beforeRenderCallbacks->executeCallables([$this->template]);

        return $templateContent;
    }

    /**
     * Processes component action
     * 
     * @param ?TemplateObject $templateContent TemplateObject instance or null
     * @return TemplateObject
     */
    private function processComponentAction(TemplateObject $templateContent) {
        // Split the component action parameter
        $do = $this->httpRequest->get('do');
        $doParts = explode('-', $do);

        if(count($doParts) < 2) {
            return null;
        }

        $componentName = $doParts[0];

        if($this->isAjax) {
            $methodName = 'action' . ucfirst($doParts[1]);
        } else {
            $methodName = 'handle' . ucfirst($doParts[1]);
        }

        // Get the arguments
        $methodArgs = [];
        if(count($doParts) > 2) {
            for($i = 2; $i < count($doParts); $i++) {
                $methodArgs[] = $doParts[$i];
            }
        }

        // Get the component
        $component = $templateContent->getComponent($componentName);

        // Handle the component action
        if($component !== null) {
            if(method_exists($component, $methodName)) {
                $result = $this->logger->stopwatch(function() use ($component, $methodName) {
                    try {
                        if($this->httpRequest->get('isFormSubmit') == '1') { // it is a form
                            $fr = $this->createFormRequest();
                            $result = $component->processMethod($methodName, [$this->httpRequest, $fr]);
                        } else {
                            $result = $component->processMethod($methodName, [$this->httpRequest]);
                        }
                    } catch(AException|Exception $e) {
                        if(!($e instanceof AException)) {
                            try {
                                throw new GeneralException('Could not process component request. Reason: ' . $e->getMessage(), $e);
                            } catch(AException $e) {
                                return ['error' => '1', 'errorMsg' => 'Error: ' . $e->getMessage()];
                            }
                        }
                        throw $e;
                    }
                    return $result;
                }, $componentName . '::' . $methodName);
    
                if($result !== null) {
                    if($result instanceof AResponse) {
                        return TemplateObject::createFromAResponse($result);
                    } else {
                        return new TemplateObject(json_encode($result));
                    }
                } else {
                    throw new NoAjaxResponseException();
                }
            } else {
                throw new ActionDoesNotExistException('Method \'' . $component::class . '::' . $methodName . '()\' does not exist.');
            }
        } else {
            return null;
        }
    }

    /**
     * Processes handle<Name>() action
     * 
     * @param string $moduleName Module name
     * @param bool $ok
     */
    private function processHandle(string $moduleName, bool &$ok) {
        $handleAction = 'handle' . ucfirst($this->action);
        
        $ok = true;
        $params = $this->getQueryParams();
        $this->logger->stopwatch(function() use ($handleAction, $params) {
            if(isset($params['isFormSubmit']) == '1') {
                $fr = $this->createFormRequest();
                return $this->$handleAction($fr);
            } else {
                return $this->$handleAction();
            }
        }, 'App\\Modules\\' . $moduleName . '\\' . $this->title . '::' . $handleAction);
    }

    /**
     * Processes render<Name>() action
     * 
     * @param bool $ok
     * @return TemplateObject|null TemplateObject or null
     */
    private function processRender(bool &$ok) {
        $ok = true;
        
        $templatePath = __DIR__ . '\\' . $this->params['module'] . '\\Presenters\\templates\\' . $this->name . '\\' . $this->action . '.html';

        if(!file_exists($templatePath)) {
            throw new TemplateDoesNotExistException($this->action, $templatePath);
        }

        return $this->getTemplate($templatePath);
    }

    /**
     * Renders the content template and fills the system template with the rendered content template.
     * Erases presenter cache and calls custom after-render callbacks.
     */
    private function afterRender() {
        if($this->sysTemplate !== null) {
            if($this->template !== null) {
                $this->sysTemplate->sys_page_content = $this->template->render()->getRenderedContent();
            } else {
                $this->sysTemplate->sys_page_content = '';
            }

            $this->template = $this->sysTemplate;
        }

        $this->saveFlashMessagesToCache();

        $this->presenterCache->reset();

        $this->afterRenderCallbacks->executeCallables();
    }

    /**
     * Processes AJAX action
     * 
     * @param string $moduleName Module name
     * @return TemplateObject|null Template object or null
     */
    private function processAction(string $moduleName) {
        $actionAction = 'action' . ucfirst($this->action);

        if(method_exists($this, $actionAction)) {
            $result = $this->logger->stopwatch(function() use ($actionAction) {
                try {
                    $result = $this->$actionAction($this->httpRequest);
                } catch(AException|Exception $e) {
                    throw $e;
                    return ['error' => '1', 'errorMsg' => 'Error: ' . $e->getMessage()];
                }
                return $result;
            }, 'App\\Modules\\' . $moduleName . '\\' . $this->title . '::' . $actionAction);

            if($result !== null) {
                if($result instanceof AResponse) {
                    return TemplateObject::createFromAResponse($result);
                } else {
                    return new TemplateObject(json_encode($result));
                }
            } else {
                throw new NoAjaxResponseException();
            }
        }

        return null;
    }

    /**
     * Adds external JS script to the page
     * 
     * @param string $scriptPath Path to the JS script
     * @param bool True if type should be added or false if not
     */
    protected function addExternalScript(string $scriptPath, bool $hasType = true) {
        $this->scripts->add(null, '<script ' . ($hasType ? 'type="text/javascript" ' : '') . 'src="' . $scriptPath . '"></script>');
    }

    /**
     * Adds JS script to the page
     * 
     * @param AjaxRequestBuilder|AAjaxRequest|string $scriptContent JS script content
     */
    public function addScript(AjaxRequestBuilder|AAjaxRequest|string $scriptContent) {
        if($scriptContent instanceof AjaxRequestBuilder) {
            $scriptContent = $scriptContent->build();
        } else if($scriptContent instanceof AAjaxRequest) {
            $code = $scriptContent->build();
            $scriptContent->checkChecks();
            $scriptContent = $code;
        }
        
        $this->scripts->add(null, '<script type="text/javascript">' . $scriptContent . '</script>');
    }

    /**
     * Saves flash messages to the session
     */
    private function saveFlashMessagesToCache() {
        if(!empty($this->flashMessages)) {
            $this->httpSessionSet(SessionNames::FLASH_MESSAGES, $this->flashMessages);
            $this->logger->warning('Flash messages saved to cache: ' . var_export($this->flashMessages, true), __METHOD__);
        }
    }

    /**
     * Creates a link leading to an action in current presenter. It is used for links back.
     * 
     * @param string $action Action name
     * @param array $params Parameters
     * @param string $class Link CSS class
     * @return string HTML code
     */
    protected function createBackUrl(string $action, array $params = [], string $class = 'link') {
        return LinkBuilder::createSimpleLink('&larr; Back', $this->createURL($action, $params), $class);
    }

    /**
     * Creates a link leading to an action in a presenter in a module. It is used for links back.
     * 
     * @param string $modulePresenter Module and presenter name
     * @param string $action Action name
     * @param array $params Parameters
     * @param string $class Link CSS class
     * @return string HTML code
     */
    protected function createBackFullUrl(string $modulePresenter, string $action, array $params = [], string $class = 'link') {
        return LinkBuilder::createSimpleLink('&larr; Back', $this->createFullURL($modulePresenter, $action, $params), $class);
    }
}

?>