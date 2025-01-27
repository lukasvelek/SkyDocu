<?php

namespace App\Modules;

use App\Components\ProcessForm\CommonProcessForm;
use App\Components\Sidebar\Sidebar2;
use App\Core\Caching\CacheFactory;
use App\Core\Http\HttpRequest;
use App\Helpers\GridHelper;
use App\UI\AComponent;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\GridBuilder2\GridBuilder;

/**
 * Component factory is used for getting instances of components
 * 
 * @author Lukas Velek
 */
class ComponentFactory {
    protected HttpRequest $request;
    protected APresenter $presenter;

    private ?CacheFactory $cacheFactory;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HTTP request
     * @param APresenter $presenter Current presenter instance
     */
    public function __construct(HttpRequest $request, APresenter $presenter) {
        $this->request = $request;
        $this->presenter = $presenter;

        $this->cacheFactory = null;
    }

    /**
     * Returns a GridBuilder instance
     * 
     * @return GridBuilder GridBuilder instance
     */
    public function getGridBuilder(?string $containerId = null) {
        $grid = new GridBuilder($this->request);
        $helper = new GridHelper($this->presenter->logger, $this->presenter->getUserId(), $containerId);
        $grid->setHelper($helper);
        $grid->setCacheFactory($this->getCacheFactory());
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
     * Returns instance of CommonProcessForm
     * 
     * @return CommonProcessForm CommonProcessForm instance
     */
    public function getCommonProcessForm() {
        $form = new CommonProcessForm($this->request);
        $this->injectDefault($form);
        return $form;
    }

    /**
     * Injects default variables
     * 
     * @param AComponent &$component Component
     */
    private function injectDefault(AComponent &$component) {
        $component->setApplication($this->presenter->app);
        $component->setPresenter($this->presenter);
    }
}

?>