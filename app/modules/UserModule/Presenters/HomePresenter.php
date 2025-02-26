<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesGrid\ProcessesGrid;
use App\Constants\Container\ProcessGridViews;
use App\Core\Http\HttpRequest;

class HomePresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function handleDashboard() {
        $container = $this->app->containerManager->getContainerById($this->containerId);

        if($container->getPermanentFlashMessage() !== null) {
            $code = $this->createFlashMessage('info', $container->getPermanentFlashMessage(), 0, false, true);
            $this->saveToPresenterCache('permanentFlashMessage', $code);
        } else {
            $this->saveToPresenterCache('permanentFlashMessage', '');
        }
    }

    public function renderDashboard() {
        $this->template->permanent_flash_message = $this->loadFromPresenterCache('permanentFlashMessage');
    }

    protected function createComponentProcessesWaitingForMeGrid(HttpRequest $request) {
        $grid = new ProcessesGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->gridManager,
            $this->processManager,
            $this->documentManager
        );

        $grid->disableActions();
        $grid->disablePagination();
        $grid->disableControls();
        $grid->setView(ProcessGridViews::VIEW_WAITING_FOR_ME);

        return $grid;
    }

    protected function createComponentProcessesStartedByMeGrid(HttpRequest $request) {
        $grid = new ProcessesGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->gridManager,
            $this->processManager,
            $this->documentManager
        );

        $grid->disableActions();
        $grid->disablePagination();
        $grid->disableControls();

        $grid->setView(ProcessGridViews::VIEW_STARTED_BY_ME);

        return $grid;
    }
}

?>