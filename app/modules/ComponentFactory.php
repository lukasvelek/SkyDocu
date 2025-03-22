<?php

namespace App\Modules;

use App\Components\ProcessForm\CommonProcessForm;
use App\Components\ProcessForm\Processes\AProcessForm;
use App\Components\ProcessForm\Processes\ContainerRequest;
use App\Components\ProcessForm\Processes\FunctionRequest;
use App\Components\ProcessForm\Processes\HomeOffice;
use App\Components\ProcessForm\Processes\Invoice;
use App\Components\Sidebar\Sidebar2;
use App\Constants\Container\StandaloneProcesses;
use App\Core\Caching\CacheFactory;
use App\Core\Http\HttpRequest;
use App\Helpers\GridHelper;
use App\Managers\Container\StandaloneProcessManager;
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
     * Returns an instance of a Standalone process by it's name
     * 
     * @param string $name Standalone process name
     */
    public function getStandaloneProcessFormByName(
        string $name,
        StandaloneProcessManager $standaloneProcessManager
    ): ?AProcessForm {
        switch($name) {
            case StandaloneProcesses::HOME_OFFICE:
                $form = new HomeOffice($this->request);
                $this->injectDefault($form, $name);
                return $form;

            case StandaloneProcesses::FUNCTION_REQUEST:
                $form = new FunctionRequest($this->request);
                $this->injectDefault($form, $name);
                return $form;

            case StandaloneProcesses::INVOICE:
                $form = new Invoice($this->request, $standaloneProcessManager);
                $this->injectDefault($form, $name);
                return $form;

            case StandaloneProcesses::CONTAINER_REQUEST:
                $form = new ContainerRequest($this->request);
                $this->injectDefault($form, $name);
                return $form;

            default:
                return null;
        }
    }
}

?>