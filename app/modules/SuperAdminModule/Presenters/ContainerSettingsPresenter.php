<?php

namespace App\Modules\SuperAdminModule;

use App\Constants\ContainerStatus;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;

class ContainerSettingsPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ContainerSettingsPresenter', 'Container settings');
    }

    public function handleHome() {}

    public function renderHome() {}

    public function handleStatus(?FormResponse $fr = null) {
        $containerId = $this->httpGet('containerId', true);

        if($fr !== null) {
            try {
                $this->app->containerRepository->beginTransaction(__METHOD__);

                $this->app->containerManager->changeContainerStatus($containerId, $fr->status, $this->getUserId(), $fr->description);

                $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Container status changed.', 'success');
            } catch(AException $e) {
                $this->app->containerRepository->rollback(__METHOD__);

                $this->flashMessage('Could not change container status. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('status', ['containerId' => $containerId]));
        }
    }

    public function renderStatus() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('Show history', $this->createURL('listStatusHistory', ['containerId' => $this->httpGet('containerId')]), 'link')
        ];
    }

    protected function createComponentContainerStatusForm(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($request->query['containerId']);

        $form = new FormBuilder2($request);

        $form->setAction($this->createURL('status', ['containerId' => $request->query['containerId']]));

        $disabled = false;
        $statuses = [];
        foreach(ContainerStatus::getAll() as $key => $value) {
            if($container->status == ContainerStatus::NEW || $container->status == ContainerStatus::IS_BEING_CREATED) {
                $status = [
                    'text' => $value,
                    'value' => $key
                ];

                if($container->status == $key) {
                    $status['selected'] = 'selected';
                }

                $statuses[] = $status;
                $disabled = true;
            } else {
                if(in_array($key, [ContainerStatus::IS_BEING_CREATED, ContainerStatus::NEW])){
                    continue;
                }

                $status = [
                    'text' => $value,
                    'value' => $key
                ];

                if($container->status == $key) {
                    $status['selected'] = 'selected';
                }

                $statuses[] = $status;
            }
        }

        $form->addSelect('status', 'Status:')
            ->addRawOptions($statuses)
            ->setDisabled($disabled);

        $form->addTextArea('description', 'Description:')
            ->setRequired();

        $form->addSubmit('Save');

        return $form;
    }

    public function renderListStatusHistory() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('status', ['containerId' => $this->httpGet('containerId')]), 'link')
        ];
    }

    protected function createComponentContainerStatusHistoryGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->containerRepository->composeQueryForContainerStatusHistory($request->query['containerId']), 'historyId');
        $grid->addQueryDependency('containerId', $request->query['containerId']);

        $grid->addColumnUser('userId', 'User');
        $grid->addColumnText('description', 'Description');
        $grid->addColumnConst('oldStatus', 'Old status', ContainerStatus::class);
        $grid->addColumnConst('newStatus', 'New status', ContainerStatus::class);

        return $grid;
    }
}

?>