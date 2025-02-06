<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Components\BackgroundServicesGrid\BackgroundServicesGrid;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;

class BackgroundServicesPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('BackgroundServicesPresenter', 'Background services');
    }

    public function renderList() {
        $this->template->links = [];
    }

    public function createComponentBgServicesGrid() {
        $grid = new BackgroundServicesGrid(
            $this->componentFactory->getGridBuilder(),
            $this->app,
            $this->app->systemServicesRepository
        );

        return $grid;
    }

    public function handleRun() {
        $serviceId = $this->httpRequest->get('serviceId');
        if($serviceId === null) {
            throw new RequiredAttributeIsNotSetException('serviceId');
        }

        try {
            $service = $this->app->systemServicesRepository->getServiceById($serviceId);

            if($service === null) {
                throw new GeneralException('Service does not exist.');
            }

            if(!$this->app->serviceManager->runService($service->getScriptPath())) {
                throw new GeneralException('Could not run service.');
            }

            sleep(2);

            $this->flashMessage('Service run.', 'success');
        } catch(AException $e) {
            $this->flashMessage('Could not run service. Reason: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect($this->createURL('list'));
    }
}

?>