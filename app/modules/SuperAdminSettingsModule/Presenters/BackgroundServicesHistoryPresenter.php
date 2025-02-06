<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\SystemServiceHistoryStatus;
use App\Core\Http\HttpRequest;

class BackgroundServicesHistoryPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('BackgroundServicesHistoryPresenter', 'Background services history');
    }

    public function renderList() {
        $this->template->links = $this->createBackFullUrl('SuperAdminSettings:BackgroundServices', 'list');
    }

    public function createComponentBgServiceHistoryGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->systemServicesRepository->composeQueryForServiceHistory($request->get('serviceId')), 'historyId');
        $grid->addQueryDependency('serviceId', $request->get('serviceId'));

        $grid->addColumnConst('status', 'Status', SystemServiceHistoryStatus::class);

        $grid->addColumnDatetime('dateCreated', 'Date');

        return $grid;
    }
}

?>