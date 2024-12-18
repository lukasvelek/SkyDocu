<?php

namespace App\Modules;

use App\Components\Sidebar\Sidebar2;
use App\Core\Http\HttpRequest;
use App\Helpers\GridHelper;
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

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HTTP request
     * @param APresenter $presenter Current presenter instance
     */
    public function __construct(HttpRequest $request, APresenter $presenter) {
        $this->request = $request;
        $this->presenter = $presenter;
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
        return $grid;
    }
    
    /**
     * Returns a FormBuilder2 instance
     * 
     * @return FormBuilder2 FormBuilder2 instance
     */
    public function getFormBuilder() {
        $form = new FormBuilder2($this->request);
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
}

?>