<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesGrid\ProcessesGrid;
use App\Components\ProcessViewsSidebar\ProcessViewsSidebar;
use App\Constants\Container\ProcessGridViews;
use App\Constants\JobQueueTypes;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;

class ProcessesPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');
    }

    public function renderList() {
        $this->template->links = [];

        $gridPage = 0;
        if($this->httpRequest->get('gridPage') !== null) {
            $gridPage = $this->httpRequest->get('gridPage');
        }

        $view = $this->httpRequest->get('view');

        if($view == ProcessGridViews::VIEW_WAITING_FOR_ME) {
            /**
             * This JS script automatically refreshes the processes grid every 60 seconds
             */
            $this->addScript('
                const GRID_PAGE = ' . $gridPage . ';
                const VIEW = "' . $view . '";
                const DELAY_S = 60;

                async function autoUpdate() {
                    await sleep(DELAY_S * 1000);
                    await processesGrid_gridRefresh(GRID_PAGE, VIEW);
                    await autoUpdate();
                }

                autoUpdate();
            ');
        }
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
            $this->processManager,
            $this->containerProcessAuthorizator
        );

        return $processGrid;
    }

    public function handleDeleteInstance() {
        $instanceId = $this->httpRequest->get('instanceId');
        $view = $this->httpRequest->get('view');

        try {
            $this->app->jobQueueRepository->beginTransaction(__METHOD__);

            $this->app->jobQueueManager->insertNewJob(JobQueueTypes::DELETE_CONTAINER_PROCESS_INSTANCE, [
                'instanceId' => $instanceId,
                'containerId' => $this->containerId
            ], null);

            $this->app->jobQueueRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Process instance was enqueued for deletion.', 'success');
        } catch(AException $e) {
            $this->app->jobQueueRepository->rollback(__METHOD__);

            $this->flashMessage('Could not enqueue process instance for deletion. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list', ['view' => $view]));
    }

    public function handleCancelInstance() {
        $instanceId = $this->httpRequest->get('instanceId');
        $view = $this->httpRequest->get('view');

        try {
            $this->app->jobQueueRepository->beginTransaction(__METHOD__);

            $this->app->jobQueueManager->insertNewJob(JobQueueTypes::CANCEL_CONTAINER_PROCESS_INSTANCE, [
                'instanceId' => $instanceId,
                'containerId' => $this->containerId,
                'userId' => $this->getUserId()
            ], null);

            $this->app->jobQueueRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Process instance was enqueued for cancelation.', 'success');
        } catch(AException $e) {
            $this->app->jobQueueRepository->rollback(__METHOD__);

            $this->flashMessage('Could not enqueue process instance for cancelation. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list', ['view' => $view]));
    }
}

?>