<?php

namespace App\Modules\SuperAdminModule;

use App\Constants\ContainerStatus;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;

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
        } else {        
            $container = $this->app->containerManager->getContainerById($containerId);

            $statuses = [];
            foreach(ContainerStatus::getAll() as $key => $value) {
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

            $form = new FormBuilder();

            $form->setAction($this->createURL('status', ['containerId' => $containerId]))
                ->addSelect('status', 'Status:', $statuses, true)
                ->addTextArea('description', 'Description:', null, true)
                ->addSubmit('Save')
            ;

            $this->saveToPresenterCache('form', $form);
        }
    }

    public function renderStatus() {
        $this->template->form = $this->loadFromPresenterCache('form');
    }
}

?>