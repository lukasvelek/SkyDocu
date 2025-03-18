<?php

namespace App\Modules;

use App\Components\Navbar\Navbar;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\TemplateDoesNotExistException;
use App\Logger\Logger;
use App\Managers\Container\GroupManager;
use Exception;

/**
 * The common module abstract class that every module must extend. It contains functions used for rendering the page content.
 * 
 * @author Lukas Velek
 */
abstract class AModule extends AGUICore {
    protected string $title;

    protected array $presenters;

    private array $flashMessages;
    protected ?TemplateObject $template;
    protected ?Logger $logger;
    protected CacheFactory $cacheFactory;

    private bool $isAjax;

    /**
     * The class constructor
     * 
     * @param string $title Module title
     */
    protected function __construct(string $title) {
        $this->presenters = [];
        $this->title = $title;
        $this->flashMessages = [];
        $this->template = null;
        $this->logger = null;
        $this->isAjax = false;
        $this->module = $this;
    }

    /**
     * Does the call come from AJAX?
     * 
     * @param bool $isAjax Is AJAX?
     */
    public function setAjax(bool $isAjax) {
        $this->isAjax = $isAjax;
    }

    /**
     * Sets the logger instance
     * 
     * @param Logger $logger Logger instance
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Sets the CacheFactory instance
     * 
     * @param CacheFactory $cacheFactory CacheFactory instance
     */
    public function setCacheFactory(CacheFactory $cacheFactory) {
        $this->cacheFactory = $cacheFactory;
    }

    /**
     * Loads all presenters associated with the extending module
     */
    public function loadPresenters() {
        $presenters = [];

        $declaredClasses = get_declared_classes();

        foreach($declaredClasses as $declaredClass) {
            if(str_starts_with($declaredClass, 'App\\Modules\\' . $this->title) && str_ends_with($declaredClass, 'Presenter')) {
                $presenters[] = $declaredClass;
            }
        }

        $this->presenters = $presenters;
    }

    /**
     * Performs operations needed to be done before rendering, then renders the presenter and finally returns the rendered content
     * 
     * @param string $presenterTitle Presenter title
     * @param string $actionTitle Action title
     * @return string Rendered page content
     */
    public function render(string $presenterTitle, string $actionTitle) {
        try {
            $this->startup($presenterTitle, $actionTitle);
        
            $this->renderPresenter();
            $this->renderModule();

            return $this->template->render()->getRenderedContent();
        } catch(AException|Exception $e) {
            throw $e;
        }
    }

    /**
     * Renders custom module page content. Currently not in use.
     */
    public function renderModule() {}

    /**
     * Renders the presenter and fetches the TemplateObject instance. It also renders flash messages.
     */
    public function renderPresenter() {
        try {
            $this->template = $this->presenter->render($this->title);
        } catch(AException|Exception $e) {
            throw $e;
        }

        if(!$this->isAjax) {
            $this->fillFlashMessages();
        }
    }

    /**
     * Loads flash messages from cache to the local module cache and prepares it for rendering.
     */
    public function loadFlashMessagesFromCache() {
        if(isset($_GET['page']) && ($_GET['page'] == 'Anonym:Login') && isset($_GET['action']) && ($_GET['action'] == 'checkLogin')) {
            return;
        }

        if(isset($_GET['_fm'])) {
            $cacheFactory = clone $this->cacheFactory;

            if(array_key_exists('container', $_SESSION)) {
                $containerId = $_SESSION['container'];
                $cacheFactory->setCustomNamespace($containerId);
            }

            $cache = $cacheFactory->getCache(CacheNames::FLASH_MESSAGES);

            $flashMessages = $cache->load($_GET['_fm'], function() { return []; });
            
            if(empty($flashMessages)) {
                return;
            }

            foreach($flashMessages as $flashMessage) {
                $autoCloseLength = 5;
                if(isset($flashMessage['autoClose'])) {
                    $autoCloseLength = $flashMessage['autoClose'];
                }

                $this->flashMessages[] = $this->createFlashMessage($flashMessage['type'], $flashMessage['text'], count($this->flashMessages), false, false, $autoCloseLength);
            }

            $cache->invalidate();
        }
    }

    /**
     * Fills the template with flash messages and also with permanent flash messages defined in the presenter
     */
    private function fillFlashMessages() {
        $fmCode = '';

        if(count($this->flashMessages) > 0) {
            $fmCode = implode('<br>', $this->flashMessages);
        }

        $fmCode .= $this->presenter->fillPermanentFlashMessages();
        
        $this->template->sys_flash_messages = $fmCode;
    }

    /**
     * Returns the default layout template. It can be the common one used for all modules or it can be a custom one.
     * 
     * @return TemplateObject Page layout TemplateObject instance
     */
    private function getCommonTemplate() {
        $commonLayout = __DIR__ . '\\@layout\\common.html';
        $customLayout = __DIR__ . '\\' . $this->title . '\\Presenters\\templates\\@layout\\common.html';

        $template = $this->getTemplate($customLayout);
        if($template === null) {
            $template = $this->getTemplate($commonLayout);
        }
        if($template === null) {
            throw new TemplateDoesNotExistException('common.html');
        }

        return $template;
    }

    /**
     * Performs operations that must be done before rendering the presenter. Here is the default layout template loaded, presenter instantiated and flash messages loaded from cache.
     * 
     * @param string $presenterTitle Presenter title
     * @param string $actionTitle Action title
     * @param bool $isAjax Is the request called from AJAX?
     */
    protected function startup(string $presenterTitle, string $actionTitle) {
        $this->template = $this->getCommonTemplate();

        $this->presenter = $this->createPresenterInstance($presenterTitle);
        $this->presenter->setTemplate($this->isAjax ? null : $this->template);
        $this->presenter->setParams(['module' => $this->title]);
        $this->presenter->setAction($actionTitle);
        $this->presenter->setLogger($this->logger);
        $this->presenter->setIsAjax($this->isAjax);
        $this->presenter->setApplication($this->app);
        $this->presenter->setHttpRequest($this->httpRequest);
        $this->presenter->setPresenter($this->presenter);
        $this->presenter->setModule($this);
        $this->presenter->lock();
        $this->presenter->setCacheFactory(clone $this->cacheFactory);
        
        $this->presenter->startup();
    }

    /**
     * Creates an instance of presenter with given name
     * 
     * @param string $presenterTitle Presenter title
     * @return APresenter Presenter instance
     */
    public function createPresenterInstance(string $presenterTitle) {
        $realPresenterTitle = 'App\\Modules\\' . $this->title . '\\' . $presenterTitle;

        return new $realPresenterTitle();
    }

    /**
     * Returns the module name
     * 
     * @return string Module name
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Creates navbar instance using given parameters
     * 
     * @param int $mode Navbar mode
     * @param null|GroupManager $groupManager GroupManager instance or null
     * @return Navbar Navbar instance
     */
    protected function createNavbarInstance(?int $mode, ?GroupManager $groupManager) {
        $navbar = new Navbar($this->httpRequest, $mode, $this->app->currentUser, $groupManager);
        
        $navbar->setComponentName('navbar');
        $navbar->setCacheFactory(clone $this->cacheFactory);

        return $navbar;
    }

    /**
     * Checks if a presenter with given name exists
     * 
     * @param string $presenterName Presenter name
     * @return bool True if exists or false if not
     */
    public function checkPresenterExists(string $presenterName) {
        if(empty($this->presenters)) {
            return false;
        }

        foreach($this->presenters as $presenter) {
            if(str_contains($presenter, $presenterName)) {
                return true;
            }
        }

        return false;
    }
}

?>