<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesGrid\ProcessesGrid;
use App\Components\ProcessViewsSidebar\ProcessViewsSidebar;
use App\Constants\Container\ProcessGridViews;
use App\Constants\JobQueueTypes;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\GridBuilder2\CheckboxLink;
use App\UI\LinkBuilder;

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

                function processBulkAction(data) {
                    post(data.url, {"ids": data.ids});
                }
            ');
        }
    }

    protected function createComponentProcessViewsSidebar(HttpRequest $request) {
        $sidebar = new ProcessViewsSidebar($request);

        return $sidebar;
    }

    protected function createComponentProcessesGrid(HttpRequest $request) {
        $grid = new ProcessesGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->processInstanceRepository,
            $this->httpRequest->get('view'),
            $this->groupManager,
            $this->processManager,
            $this->containerProcessAuthorizator
        );

        $grid->useCheckboxes($this);

        $grid->addCheckboxLinkCallback(
            (new CheckboxLink('cancelInstance'))
                ->setCheckCallback(function(string $primaryKey) {
                    return $this->containerProcessAuthorizator->canUserCancelProcessInstance($primaryKey, $this->getUserId());
                })
                ->setLinkCallback(function(array $primaryKeys) {
                    $data = [
                        'ids' => $primaryKeys,
                        'url' => $this->createURLString('cancelInstances', ['view' => $this->httpRequest->get('view')])
                    ];

                    return LinkBuilder::createJSOnclickLink(
                        'Cancel instance',
                        'processBulkAction(' . htmlspecialchars(json_encode($data)) . ')',
                        'link'
                    );
                })
        );

        $grid->addCheckboxLinkCallback(
            (new CheckboxLink('deleteInstance'))
                ->setCheckCallback(function(string $primaryKey) {
                    return $this->containerProcessAuthorizator->canUserDeleteProcessInstance($primaryKey, $this->getUserId());
                })
                ->setLinkCallback(function(array $primaryKeys) {
                    $data = [
                        'ids' => $primaryKeys,
                        'url' => $this->createURLString('deleteInstances', ['view' => $this->httpRequest->get('view')])
                    ];

                    return LinkBuilder::createJSOnclickLink(
                        'Delete instance',
                        'processBulkAction(' . htmlspecialchars(json_encode($data)) . ')',
                        'link'
                    );
                })
        );

        return $grid;
    }

    public function handleDeleteInstances() {
        $instanceIds = explode(',', $this->httpRequest->post('ids'));
        $view = $this->httpRequest->get('view');

        try {
            $this->app->jobQueueRepository->beginTransaction(__METHOD__);

            $this->app->jobQueueManager->insertNewJob(
                JobQueueTypes::DELETE_CONTAINER_PROCESS_INSTANCE, 
                [
                    'instanceIds' => $instanceIds,
                    'containerId' => $this->containerId
                ],
                null);

            $this->app->jobQueueRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Process instances were enqueued for deletion.', 'success');
        } catch(AException $e) {
            $this->app->jobQueueRepository->rollback(__METHOD__);

            $this->flashMessage('Could not enqueue process instances for deletion. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list', ['view' => $view]));
    }

    public function handleCancelInstances() {
        $instanceIds = explode(',', $this->httpRequest->post('ids'));
        $view = $this->httpRequest->get('view');

        try {
            $this->app->jobQueueRepository->beginTransaction(__METHOD__);

            $this->app->jobQueueManager->insertNewJob(
                JobQueueTypes::CANCEL_CONTAINER_PROCESS_INSTANCE,
                [
                    'instanceIds' => $instanceIds,
                    'containerId' => $this->containerId,
                    'userId' => $this->getUserId()
                ],
                null
            );

            $this->app->jobQueueRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Process instances were enqueued for cancelation.', 'success');
        } catch(AException $e) {
            $this->app->jobQueueRepository->rollback(__METHOD__);

            $this->flashMessage('Could not enqueue process instances for cancelation. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list', ['view' => $view]));
    }
}

?>