<?php

namespace App\Modules\AdminModule;

class ProcessReportsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessReportsPresenter', 'Process reports');

        $this->setProcesses();
    }

    public function renderList() {

    }

    protected function createComponentProcessReportsGrid() {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->processReportManager->composeQueryForAllVisibleReports($this->getUserId());

        $grid->createDataSourceFromQueryBuilder($qb, 'reportId');

        return $grid;
    }
}