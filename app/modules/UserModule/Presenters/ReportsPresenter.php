<?php

namespace App\Modules\UserModule;

use App\Components\ProcessReportsGrid\ProcessReportsGrid;
use App\Components\ProcessReportsSidebar\ProcessReportsSidebar;
use App\Core\Http\HttpRequest;

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
    }

    protected function createComponentProcessReportsSidebar(HttpRequest $request) {
        $sidebar = new ProcessReportsSidebar($request, $this->standaloneProcessManager, $this->supervisorAuthorizator);

        return $sidebar;
    }

    protected function createComponentReportGrid(HttpRequest $request) {
        $grid = new ProcessReportsGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->processManager,
            $this->standaloneProcessManager
        );

        if($request->get('view') !== null) {
            $viewParts = $request->get('view');
            $viewParts = explode('-', $viewParts);

            $grid->setView($viewParts[1]);
            $grid->setProcessType($viewParts[0]);
        }

        return $grid;
    }
}

?>