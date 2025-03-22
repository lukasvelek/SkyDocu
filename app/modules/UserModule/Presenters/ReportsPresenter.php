<?php

namespace App\Modules\UserModule;

use App\Components\ProcessReportsGrid\ProcessReportsGrid;
use App\Components\ProcessReportsSelect\ProcessReportsSelect;
use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;
use App\Exceptions\RequiredAttributeIsNotSetException;

class ReportsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ReportsPresenter', 'Reports');
    }

    public function renderList() {}

    protected function createComponentReportsSelect() {
        $select = new ProcessReportsSelect($this->httpRequest, $this->standaloneProcessManager);
        $select->setApplication($this->app);
        $select->setPresenter($this);

        return $select;
    }

    public function renderShowReport() {
        $name = $this->httpRequest->get('name');
        if($name === null) {
            throw new RequiredAttributeIsNotSetException('name');
        }
        $view = $this->httpRequest->get('view');
        if($view === null) {
            throw new RequiredAttributeIsNotSetException('view');
        }

        $pageTitle = ucfirst($view) . ' ' . StandaloneProcesses::toString($name) . ' requests';

        $this->template->page_title = $pageTitle;
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentReportGrid(HttpRequest $request) {
        $grid = new ProcessReportsGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->processManager,
            $this->standaloneProcessManager
        );

        $grid->setView($request->get('view') ?? $request->post('view'));
        $grid->setProcessType($request->get('name') ?? $request->post('name'));

        return $grid;
    }
}

?>