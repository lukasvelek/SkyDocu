<?php

namespace App\Components\ProcessReportsGrid;

use App\Constants\Container\GridNames;
use App\Constants\Container\ProcessesGridSystemMetadata;
use App\Constants\Container\ProcessStatus;
use App\Core\Application;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Managers\Container\ProcessManager;
use App\Managers\Container\StandaloneProcessManager;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

/**
 * ProcessReportsGrid displays available process reports to the user
 * 
 * @author Lukas Velek
 */
class ProcessReportsGrid extends GridBuilder implements IGridExtendingComponent {
    private ProcessManager $processManager;
    private StandaloneProcessManager $standaloneProcessManager;
    private string $view;
    private string $currentUserId;
    private ProcessReportsGridDataSourceHelper $dataSourceHelper;
    private string $processType;

    public function __construct(
        GridBuilder $grid,
        Application $app,
        ProcessManager $processManager,
        StandaloneProcessManager $standaloneProcessManager
    ) {
        parent::__construct($grid->httpRequest);
        $this->setHelper($grid->getHelper());
        $this->setCacheFactory($grid->cacheFactory);

        $this->app = $app;
        $this->processManager = $processManager;
        $this->standaloneProcessManager = $standaloneProcessManager;
        $this->currentUserId = $this->app->currentUser->getId();

        $this->dataSourceHelper = new ProcessReportsGridDataSourceHelper($this->standaloneProcessManager);
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

    public function prerender() {
        $this->createDataSource();

        $this->appendSystemMetadata();

        $this->appendActions();

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
        $qb = $this->dataSourceHelper->composeQuery($this->view, $this->currentUserId, $this->processType);

        $this->createDataSourceFromQueryBuilder($qb, 'processId');
    }

    /**
     * Appends system metadata to grid
     */
    private function appendSystemMetadata() {
        $metadata = $this->dataSourceHelper->getMetadataToAppendForView($this->view);

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

    /**
     * Appends actions
     */
    private function appendActions() {
        $open = $this->addAction('open');
        $open->setTitle('Open');
        $open->onCanRender[] = function() {
            return true;
        };
        $open->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $params = [
                'processId' => $primaryKey,
                'disableBackLink' => '1'
            ];

            $el = HTML::el('a');
            $el->href($this->createFullURLString('User:Processes', 'profile', $params))
                ->text('Open')
                ->class('grid-link')
                ->target('_blank');

            return $el;
        };
    }

    public function actionGetSkeleton(): JsonResponse {
        $this->prerender();

        return parent::actionGetSkeleton();
    }
}

?>