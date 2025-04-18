<?php

namespace App\Modules\SuperAdminModule;

use App\Constants\ProcessStatus;
use App\Core\Http\HttpRequest;
use App\Helpers\LinkHelper;
use App\UI\LinkBuilder;

class ProcessesPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');
    }

    public function renderList() {
        $links = [
            LinkBuilder::createSimpleLink('New process', $this->createFullURL('SuperAdmin:NewProcessEditor', 'form'), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentProcessesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->processRepository->composeQueryForProcesses();

        $grid->createDataSourceFromQueryBuilder($qb, 'processId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnUser('userId', 'Author');
        $grid->addColumnConst('status', 'Status', ProcessStatus::class);

        return $grid;
    }
}

?>