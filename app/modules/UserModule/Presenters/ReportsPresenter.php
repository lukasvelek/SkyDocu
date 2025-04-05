<?php

namespace App\Modules\UserModule;

use App\Components\ProcessReportsGrid\ProcessReportsGrid;
use App\Components\ProcessReportsSidebar\ProcessReportsSidebar;
use App\Components\PropertyItemsReportsGrid\PropertyItemsReportsGrid;
use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;
use App\Repositories\Container\PropertyItemsRepository;

class ReportsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ReportsPresenter', 'Reports');
    }

    public function renderList() {
        if($this->httpRequest->get('view') === null) {
            $processType = $this->standaloneProcessManager->getEnabledProcessTypes()[0];
            $view = $processType->typeKey . '-my';
            $url = $this->httpRequest->getCurrentPageActionAsArray();
            $url['view'] = $view;
            $this->redirect($url);
        }

        $this->template->links = '';
    }

    protected function createComponentProcessReportsSidebar(HttpRequest $request) {
        $sidebar = new ProcessReportsSidebar($request, $this->standaloneProcessManager, $this->supervisorAuthorizator);

        return $sidebar;
    }

    protected function createComponentReportGrid(HttpRequest $request) {
        if($request->get('view') !== null) {
            $viewParts = $request->get('view');
            $viewParts = explode('-', $viewParts);
        }

        if($viewParts[0] == 'propertyItems') {
            $grid = new PropertyItemsReportsGrid(
                $this->componentFactory->getGridBuilder($this->containerId),
                $this->app,
                new PropertyItemsRepository($this->gridRepository->conn, $this->logger),
                $this->standaloneProcessManager
            );

            if($request->get('view') !== null) {
                $grid->setView($request->get('view'));
            }
        } else {
            $grid = new ProcessReportsGrid(
                $this->componentFactory->getGridBuilder($this->containerId),
                $this->app,
                $this->processManager,
                $this->standaloneProcessManager
            );
    
            if($request->get('view') !== null) {
                $grid->setView($viewParts[1]);
                $grid->setProcessType($viewParts[0]);
            }
        }

        return $grid;
    }
}

?>