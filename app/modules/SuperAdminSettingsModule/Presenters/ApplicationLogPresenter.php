<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\ApplicationLogTypes;

class ApplicationLogPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('ApplicationLogPresenter', 'Application log');
    }

    public function renderList() {}

    protected function createComponentAppLogGrid() {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->appLogRepository->composeQueryForApplicationLog();

        $grid->createDataSourceFromQueryBuilder($qb, 'logId');

        $grid->addColumnText('message', 'Message');
        $grid->addColumnText('method', 'Method');
        $grid->addColumnConst('type', 'Type', ApplicationLogTypes::class);
        $grid->addColumnDatetime('dateCreated', 'Date created');

        return $grid;
    }
}