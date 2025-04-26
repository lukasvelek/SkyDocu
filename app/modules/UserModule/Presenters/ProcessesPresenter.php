<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesGrid\ProcessesGrid;
use App\Components\ProcessViewsSidebar\ProcessViewsSidebar;
use App\Core\Http\HttpRequest;

class ProcessesPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');
    }

    public function renderList() {
        $this->template->links = [];
    }

    protected function createComponentProcessViewsSidebar(HttpRequest $request) {
        $sidebar = new ProcessViewsSidebar($request);

        return $sidebar;
    }

    protected function createComponentProcessesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);
        $grid->setApplication($this->app);

        $processGrid = new ProcessesGrid(
            $grid,
            $this->processInstanceRepository,
            $request->get('view'),
            $this->groupManager,
            $this->processManager
        );

        return $processGrid;
    }
}

?>