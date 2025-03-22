<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\SystemServiceHistoryStatus;
use App\Core\Http\HttpRequest;

class BackgroundServicesHistoryPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('BackgroundServicesHistoryPresenter', 'Background services history');
    }

    public function renderList() {
        $serviceId = $this->httpRequest->get('serviceId');
        $service = $this->app->systemServicesRepository->getServiceById($serviceId);

        if($service->getParentServiceId() !== null) {
            $this->template->links = $this->createBackFullUrl('SuperAdminSettings:BackgroundServices', 'list', ['serviceId' => $service->getParentServiceId()]);
        } else {
            $this->template->links = $this->createBackFullUrl('SuperAdminSettings:BackgroundServices', 'list');
        }
    }

    public function createComponentBgServiceHistoryGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->systemServicesRepository->composeQueryForServiceHistory($request->get('serviceId'));
        $qb->orderBy('dateCreated', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'historyId');
        $grid->addQueryDependency('serviceId', $request->get('serviceId'));

        $grid->addColumnConst('status', 'Status', SystemServiceHistoryStatus::class);
        $grid->addColumnText('exception', 'Exception');
        $grid->addColumnText('args', 'Arguments');
        $grid->addColumnDatetime('dateCreated', 'Date');

        return $grid;
    }
}

?>