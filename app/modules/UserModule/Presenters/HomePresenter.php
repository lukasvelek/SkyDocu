<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesGrid\ProcessesGrid;
use App\Constants\Container\ProcessGridViews;
use App\Core\Http\HttpRequest;

class HomePresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function renderDashboard() {
        $container = $this->app->containerManager->getContainerById($this->containerId);

        $code = null;
        if($container->getPermanentFlashMessage() !== null) {
            $code = $this->createFlashMessage('info', $container->getPermanentFlashMessage(), 0, false, true);
        }

        $this->template->permanent_flash_message = $code ?? '';

        $this->addScript('
            /**
             * This script is responsible for asynchronous loading of widget data.
            */
            const tmp = (() => {
                new Promise((resolve) => {
                    waitingForMeWidget_gridRefresh(0, "' . ProcessGridViews::VIEW_WAITING_FOR_ME . '");
                    startedByMeWidget_gridRefresh(0, "' . ProcessGridViews::VIEW_STARTED_BY_ME . '");
                });
            });

            tmp();
        ');
    }

    protected function createComponentWaitingForMeWidget(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);
        $grid->setApplication($this->app);

        $grid = new ProcessesGrid(
            $grid,
            $this->processInstanceRepository,
            ProcessGridViews::VIEW_WAITING_FOR_ME,
            $this->groupManager,
            $this->processManager,
            $this->containerProcessAuthorizator
        );

        $grid->disableActions();
        $grid->disablePagination();
        
        if($this->isAjax()) {
            $grid->setLimit(5);
        } else {
            $grid->setLimit(0);
        }

        return $grid;
    }

    protected function createComponentStartedByMeWidget(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);
        $grid->setApplication($this->app);

        $grid = new ProcessesGrid(
            $grid,
            $this->processInstanceRepository,
            ProcessGridViews::VIEW_STARTED_BY_ME,
            $this->groupManager,
            $this->processManager,
            $this->containerProcessAuthorizator
        );

        $grid->disableActions();
        $grid->disablePagination();
        
        if($this->isAjax()) {
            $grid->setLimit(5);
        } else {
            $grid->setLimit(0);
        }

        return $grid;
    }
}

?>