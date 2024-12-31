<?php

namespace App\Components\ProcessReportsGrid;

use App\Constants\Container\GridNames;
use App\Constants\Container\ProcessesGridSystemMetadata;
use App\Constants\Container\ProcessStatus;
use App\Core\Application;
use App\Managers\Container\ProcessManager;
use App\Managers\Container\StandaloneProcessManager;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;

class ProcessReportsGrid extends GridBuilder implements IGridExtendingComponent {
    private ProcessManager $processManager;
    private StandaloneProcessManager $standaloneProcessManager;
    private string $view;
    private string $currentUserId;
    private ProcessReportsGridDataSourceHelper $dsHelper;
    private string $processType;

    public function __construct(
        GridBuilder $grid,
        Application $app,
        ProcessManager $processManager,
        StandaloneProcessManager $standaloneProcessManager
    ) {
        parent::__construct($grid->httpRequest);
        $this->setHelper($grid->getHelper());

        $this->app = $app;
        $this->processManager = $processManager;
        $this->standaloneProcessManager = $standaloneProcessManager;
        $this->currentUserId = $this->app->currentUser->getId();

        $this->dsHelper = new ProcessReportsGridDataSourceHelper($this->standaloneProcessManager);
    }

    /**
     * Sets the process type
     * 
     * @param string $processType Process type
     */
    public function setProcessType(string $processType) {
        $this->processType = $processType;
    }

    /**
     * Sets the custom view
     * 
     * @param string $view Custom view
     */
    public function setView(string $view) {
        $this->view = $view;
    }

    protected function prerender() {
        $this->createDataSource();

        $this->appendSystemMetadata();

        $this->setup();

        parent::prerender();
    }

    /**
     * Sets up the grid
     */
    private function setup() {
        $this->setGridName(GridNames::PROCESS_REPORTS_GRID);
        $this->addQueryDependency('view', $this->view);
        $this->addQueryDependency('name', $this->processType);
    }

    public function createDataSource() {
        $qb = $this->dsHelper->composeQuery($this->view, $this->currentUserId, $this->processType);

        $this->createDataSourceFromQueryBuilder($qb, 'processId');
    }

    /**
     * Appends system metadata to grid
     */
    private function appendSystemMetadata() {
        $metadata = $this->dsHelper->getMetadataToAppendForView($this->view);

        foreach($metadata as $name) {
            $text = ProcessesGridSystemMetadata::toString($name);

            switch($name) {
                case ProcessesGridSystemMetadata::AUTHOR_USER_ID:
                case ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID:
                    $this->addColumnUser($name, $text);
                    break;

                case ProcessesGridSystemMetadata::DATE_CREATED:
                    $this->addColumnDatetime($name, $text);
                    break;

                case ProcessesGridSystemMetadata::STATUS:
                    $this->addColumnConst($name, $text, ProcessStatus::class);
                    break;
            }
        }
    }
}

?>