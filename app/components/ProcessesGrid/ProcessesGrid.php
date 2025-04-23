<?php

namespace App\Components\ProcessesGrid;

use App\Constants\Container\ProcessInstanceStatus;
use App\Managers\Container\GroupManager;
use App\Repositories\Container\ProcessInstanceRepository;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;

class ProcessesGrid extends GridBuilder implements IGridExtendingComponent {
    private ProcessInstanceRepository $processInstanceRepository;
    private GroupManager $groupManager;

    private string $view;

    public function __construct(
        GridBuilder $grid,
        ProcessInstanceRepository $processInstanceRepository,
        string $view,
        GroupManager $groupManager
    ) {
        parent::__construct($grid->httpRequest);

        $this->setHelper($grid->getHelper());
        $this->setCacheFactory($grid->cacheFactory);
        $this->setApplication($grid->app);

        $this->processInstanceRepository = $processInstanceRepository;
        $this->view = $view;
        $this->groupManager = $groupManager;
    }

    public function createDataSource() {
        $dsHelper = new ProcessesGridDatasourceHelper(
            $this->view,
            $this->processInstanceRepository,
            $this->app->currentUser->getId(),
            $this->groupManager
        );

        $this->createDataSourceFromQueryBuilder($dsHelper->composeQb(), 'instanceId');
    }

    public function prerender() {
        $this->setup();

        $this->createDataSource();

        $this->fetchDataFromDb();

        $this->appendSystemMetadata();

        parent::prerender();
    }

    private function setup() {
        $this->addQueryDependency('view', $this->view);
    }

    private function appendSystemMetadata() {
        $this->addColumnText('processId', 'Process ID');
        $this->addColumnConst('status', 'Status', ProcessInstanceStatus::class);
    }
}

?>