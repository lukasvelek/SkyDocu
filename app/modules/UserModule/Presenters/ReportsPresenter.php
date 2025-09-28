<?php

namespace App\Modules\UserModule;

use App\Components\ProcessReportSidebar\ProcessReportSidebar;
use App\Helpers\LinkHelper;

class ReportsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ReportsPresenter', 'Reports');
    }

    public function handleList() {
        if($this->httpRequest->get('reportId') === null) {
            $qb = $this->processReportManager->composeQueryForAllVisibleReports($this->getUserId());
            $qb->execute();

            $reports = [];
            while($row = $qb->fetchAssoc()) {
                $reports[] = $row['reportId'];
            }

            if(!empty($reports)) {
                $this->redirect($this->createURL('list', ['reportId' => $reports[0]]));
            }

            $this->flashMessage('No report is available.', 'error', 10);
            $this->redirect($this->createFullURL('User:Home', 'dashboard'));
        }
    }

    public function renderList() {
        $links = [];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentProcessReportsSidebar() {
        $sidebar = $this->componentFactory->createComponentInstanceByClassName(ProcessReportSidebar::class, [$this->processReportManager]);

        return $sidebar;
    }

    protected function createComponentReportGrid() {
        $reportId = $this->httpRequest->get('reportId');

        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $this->processReportManager->applyReportDefinitionToGridBuilder($reportId, $grid);

        $grid->addQueryDependency('reportId', $reportId);

        return $grid;
    }
}

?>