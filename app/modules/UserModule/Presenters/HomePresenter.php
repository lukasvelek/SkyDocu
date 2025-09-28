<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesGrid\ProcessesGrid;
use App\Constants\Container\ProcessGridViews;
use App\UI\LinkBuilder;

class HomePresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function renderDashboard() {
        $container = $this->app->containerManager->getContainerById($this->containerId);

        $code = null;
        if($container->getPermanentFlashMessage() !== null) {
            $fm = $container->getPermanentFlashMessage();
            $code = $this->createPermanentFlashMessage($fm['type'], $fm['message']);
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

        $this->template->processes_waiting_for_me_widget_title = LinkBuilder::createSimpleLink('Processes waiting for me', $this->createFullURL('User:Processes', 'list', ['view' => ProcessGridViews::VIEW_WAITING_FOR_ME]), 'widget-title');
        $this->template->processes_started_by_me_widget_title = LinkBuilder::createSimpleLink('Processes started by me', $this->createFullURL('User:Processes', 'list', ['view' => ProcessGridViews::VIEW_STARTED_BY_ME]), 'widget-title');
    }

    protected function createComponentWaitingForMeWidget() {
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

        $grid->disablePagination();

        $grid->disableActionByName('workflowHistory');
        $grid->disableActionByName('cancelInstance');
        $grid->disableActionByName('deleteInstance');
        
        if($this->isAjax()) {
            $grid->setLimit(5);
        } else {
            $grid->setLimit(0);
        }

        return $grid;
    }

    protected function createComponentStartedByMeWidget() {
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

        $grid->disablePagination();

        $grid->disableActionByName('workflowHistory');
        $grid->disableActionByName('cancelInstance');
        $grid->disableActionByName('deleteInstance');
        
        if($this->isAjax()) {
            $grid->setLimit(5);
        } else {
            $grid->setLimit(0);
        }

        return $grid;
    }
}

?>