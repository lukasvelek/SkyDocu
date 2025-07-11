<?php

namespace App\Modules;

use App\Components\Sidebar\Sidebar2;
use App\Core\Application;
use App\Core\Caching\CacheFactory;
use App\Core\Http\HttpRequest;
use App\Helpers\GridHelper;
use App\UI\AComponent;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\ListBuilder\ListBuilder;

/**
 * Component factory is used for getting instances of components
 * 
 * @author Lukas Velek
 */
class ComponentFactory {
    protected HttpRequest $request;
    protected APresenter $presenter;

    private ?CacheFactory $cacheFactory;
    private Application $app;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HTTP request
     * @param APresenter $presenter Current presenter instance
     * @param Application $app Applicaiton instance
     */
    public function __construct(HttpRequest $request, APresenter $presenter, Application $app) {
        $this->request = $request;
        $this->presenter = $presenter;
        $this->app = $app;

        $this->cacheFactory = null;
    }

    /**
     * Returns a ListBuilder instance
     * 
     * @return ListBuilder ListBuilder instance
     */
    public function getListBuilder() {
        $list = new ListBuilder($this->request);
        return $list;
    }

    /**
     * Returns a GridBuilder instance
     * 
     * @return GridBuilder GridBuilder instance
     */
    public function getGridBuilder(?string $containerId = null) {
        $grid = new GridBuilder($this->request);
        $helper = new GridHelper($this->presenter->logger, $this->presenter->getUserId(), $containerId);
        $helper->setCacheFactory($this->cacheFactory);
        $grid->setHelper($helper);
        $grid->setCacheFactory($this->getCacheFactory());
        $grid->setContainerId($containerId);
        return $grid;
    }
    
    /**
     * Returns a FormBuilder2 instance
     * 
     * @return FormBuilder2 FormBuilder2 instance
     */
    public function getFormBuilder() {
        $form = new FormBuilder2($this->request);
        $this->injectDefault($form);
        return $form;
    }

    /**
     * Returns a Sidebar2 instance
     * 
     * @return Sidebar2 Sidebar2 instance
     */
    public function getSidebar() {
        $sidebar = new Sidebar2($this->request);
        return $sidebar;
    }

    /**
     * Sets custom CacheFactory instance
     * 
     * @param CacheFactory $cacheFactory CacheFactory instance
     */
    public function setCacheFactory(CacheFactory $cacheFactory) {
        $this->cacheFactory = $cacheFactory;
    }

    /**
     * Returns CacheFactory instance
     * 
     * @return CacheFactory CacheFactory instance
     */
    private function getCacheFactory() {
        return ($this->cacheFactory !== null) ? $this->cacheFactory : $this->presenter->cacheFactory;
    }

    /**
     * Injects default variables
     * 
     * @param AComponent &$component Component
     */
    private function injectDefault(AComponent &$component, ?string $processName = null) {
        $component->setApplication($this->presenter->app);
        $component->setPresenter($this->presenter);
        if($processName !== null) {
            $component->setComponentName('ProcessForm-' . $processName);
        }
    }

    /**
     * Creates a component instance by its name with custom parameters
     * 
     * @param string $className Component class name
     * @param array $params Optional constructor parameters
     */
    public function createComponentInstanceByClassName(string $className, array $params = []): AComponent {
        /**
         * @var \App\UI\AComponent $obj
         */
        $obj = new $className($this->request, ...$params);

        $obj->setApplication($this->app);
        $obj->setPresenter($this->presenter);

        return $obj;
    }
}

?>