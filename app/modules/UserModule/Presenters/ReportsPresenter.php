<?php

namespace App\Modules\UserModule;

use App\Components\ProcessReportsGrid\ProcessReportsGrid;
use App\Components\ProcessReportsSelect\ProcessReportsSelect;
use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;

class ReportsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ReportsPresenter', 'Reports');
    }

    public function renderList() {}

    protected function createComponentReportsSelect() {
        $select = new ProcessReportsSelect($this->httpRequest, $this->standaloneProcessManager);

        return $select;
    }

    public function handleShowReport() {
        $name = $this->httpGet('name', true);
        $view = $this->httpGet('view', true);

        $pageTitle = ucfirst($view) . ' ' . StandaloneProcesses::toString($name) . ' requests';

        $this->saveToPresenterCache('pageTitle', $pageTitle);

        $links = [
            $this->createBackUrl('list')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderShowReport() {
        $this->template->page_title = $this->loadFromPresenterCache('pageTitle');
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentReportGrid(HttpRequest $request) {
        $grid = new ProcessReportsGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->processManager,
            $this->standaloneProcessManager
        );

        $grid->setView($request->query['view']);
        $grid->setProcessType($request->query['name']);

        return $grid;
    }
}

?>