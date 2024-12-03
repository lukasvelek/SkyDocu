<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesGrid\ProcessesGrid;
use App\Constants\Container\ProcessGridViews;
use App\Core\Http\HttpRequest;

class ProcessesPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');
    }

    public function handleList() {
        $view = $this->httpGet('view');

        if($view === null) {
            $this->redirect($this->createURL('list', ['view' => ProcessGridViews::VIEW_ALL]));
        }
    }

    public function renderList() {
        $this->template->links = [];
    }

    protected function createComponentProcessViewsSidebar(HttpRequest $request) {
        $actives = [];
        foreach(ProcessGridViews::getAll() as $name => $title) {
            $actives[$name] = ($request->query['view'] == $name);
        }

        $sidebar = $this->componentFactory->getSidebar();

        foreach(ProcessGridViews::getAll() as $name => $title) {
            $sidebar->addLink($title, $this->createURL('list', ['view' => $name]), $actives[$name]);
        }

        return $sidebar;
    }

    protected function createComponentProcessesGrid(HttpRequest $request) {
        $grid = new ProcessesGrid(
            $this->componentFactory->getGridBuilder(),
            $this->app,
            $this->gridManager,
            $this->processManager,
            $this->documentManager
        );

        $grid->setView($request->query['view']);
    
        return $grid;
    }
}

?>