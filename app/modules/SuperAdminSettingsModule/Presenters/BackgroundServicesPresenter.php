<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Components\BackgroundServicesGrid\BackgroundServicesGrid;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\BackgroundServiceScheduleHelper;
use App\Helpers\FormHelper;

class BackgroundServicesPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('BackgroundServicesPresenter', 'Background services');
    }

    public function renderList() {
        $serviceId = $this->httpRequest->get('serviceId');

        if($serviceId === null) {
            $this->template->links = [];
        } else {
            $this->template->links = $this->createBackUrl('list');
        }
    }

    protected function createComponentBgServicesGrid(HttpRequest $request) {
        $grid = new BackgroundServicesGrid(
            $this->componentFactory->getGridBuilder(),
            $this->app,
            $this->app->systemServicesRepository
        );

        $serviceId = $request->get('serviceId');

        if($serviceId !== null) {
            $grid->setServiceId($serviceId);
            $grid->addQueryDependency('serviceId', $serviceId);
        }

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

            sleep(1);

            $this->flashMessage('Service run.', 'success');
        } catch(AException $e) {
            $this->flashMessage('Could not run service. Reason: ' . $e->getMessage(), 'error');
        }
        
        if($service->getParentServiceId() !== null) {
            $this->redirect($this->createURL('list', ['serviceId' => $service->getParentServiceId()]));
        } else {
            $this->redirect($this->createURL('list'));
        }
    }

    public function handleEditForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $serviceId = $this->httpRequest->get('serviceId');
            $service = $this->app->systemServicesRepository->getServiceById($serviceId);

            $daysArr = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

            $daysChecked = [];
            foreach($daysArr as $day) {
                $elem = 'day_' . $day;

                $daysChecked[$day] = FormHelper::isCheckboxChecked($fr, $elem);
            }

            $every = $fr->every;

            $schedule = BackgroundServiceScheduleHelper::createScheduleFromForm($daysChecked, $every);

            $isEnabled = FormHelper::isCheckboxChecked($fr, 'enabled');

            try {
                $this->app->systemServicesRepository->beginTransaction(__METHOD__);

                $this->app->systemServicesRepository->updateService($serviceId, [
                    'schedule' => $schedule,
                    'isEnabled' => $isEnabled
                ]);

                $this->app->systemServicesRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Background service changes successfully saved.', 'success');
            } catch(AException $e) {
                $this->app->systemServicesRepository->rollback(__METHOD__);

                $this->flashMessage('Could not save background service changes. Reason: ' . $e->getMessage(), 'error', 10);
            }

            if($service->getParentServiceId() !== null) {
                $this->redirect($this->createURL('list', ['serviceId' => $service->getParentServiceId()]));
            } else {
                $this->redirect($this->createURL('list'));
            }
        }
    }

    public function renderEditForm() {
        $serviceId = $this->httpRequest->get('serviceId');
        $service = $this->app->systemServicesRepository->getServiceById($serviceId);

        $this->template->service_title = $service->getTitle();

        if($service->getParentServiceId() !== null) {
            $this->template->links = $this->createBackUrl('list', ['serviceId' => $service->getParentServiceId()]);
        } else {
            $this->template->links = $this->createBackUrl('list');
        }
    }

    protected function createComponentEditServiceForm(HttpRequest $request) {
        $service = $this->app->systemServicesRepository->getServiceById($request->get('serviceId'));
        $schedule = $service->getSchedule();

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('editForm', ['serviceId' => $service->getId()]));

        $form->addLabel('lbl_general', '<b>General</b>');

        $form->addCheckboxInput('enabled', 'Service enabled:')
            ->setChecked($service->isEnabled());

        $form->addLabel('lbl_days', '<b>Schedule days</b>');
        $daysArr = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        foreach($daysArr as $day) {
            $c = $form->addCheckboxInput('day_' . $day, BackgroundServiceScheduleHelper::getFullDayNameFromShortcut($day) . ':');
            $c->setChecked(BackgroundServiceScheduleHelper::isDayEnabled($schedule, $day));
        }

        $form->addLabel('lbl_every', '<b>Schedule repeat</b>');
        $form->addNumberInput('every', 'Repeat every [minutes]:')
            ->setValue(BackgroundServiceScheduleHelper::getEvery($schedule))
            ->setMin(5)
            ->setMax(43_200 /* 1 month */);

        $form->addSubmit('Save');

        return $form;
    }
}

?>