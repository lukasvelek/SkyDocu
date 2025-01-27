<?php

namespace App\Components\BackgroundServicesGrid;

use App\Constants\SystemServiceStatus;
use App\Core\Application;
use App\Core\DB\DatabaseRow;
use App\Helpers\GridHelper;
use App\Repositories\SystemServicesRepository;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

/**
 * BackgroundServicesGrid is an extension to GridBuilder and it is used for displaying processes
 * 
 * @author Lukas Velek
 */
class BackgroundServicesGrid extends GridBuilder implements IGridExtendingComponent {
    private SystemServicesRepository $systemServicesRepository;

    /**
     * Class constructor
     * 
     * @param GridBuilder $grid
     * @param Application $app
     * @param SystemServicesRepository $systemServicesRepository
     */
    public function __construct(
        GridBuilder $grid,
        Application $app,
        SystemServicesRepository $systemServicesRepository
    ) {
        parent::__construct($grid->httpRequest);
        $this->setHelper($grid->getHelper());
        $this->setCacheFactory($grid->cacheFactory);
        $this->setApplication($app);
        
        $this->systemServicesRepository = $systemServicesRepository;
    }

    public function prerender() {
        $this->createDataSource();

        $this->fetchDataFromDb();

        $this->appendSystemMetadata();
        $this->appendActions();

        $this->setup();

        parent::prerender();
    }

    public function createDataSource() {
        $qb = $this->systemServicesRepository->composeQueryForServices();

        $this->createDataSourceFromQueryBuilder($qb, 'serviceId');
    }

    /**
     * Sets up the grid
     */
    private function setup() {
        $this->setGridName(GridHelper::GRID_BACKGROUND_SERVICES);
    }

    /**
     * Appends actions to grid
     */
    private function appendActions() {
        $run = $this->addAction('run');
        $run->setTitle('Run');
        $run->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return $row->status == 1;
        };
        $run->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdminSettings:BackgroundServices', 'run', ['serviceId' => $primaryKey]))
                ->text('Run');

            return $el;
        };

        $history = $this->addAction('history');
        $history->setTitle('History');
        $history->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return true;
        };
        $history->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdminSettings:BackgroundServicesHistory', 'list', ['serviceId' => $primaryKey]))
                ->text('History');

            return $el;
        };
    }
    
    /**
     * Appends system metadata to grid
     */
    private function appendSystemMetadata() {
        $this->addColumnText('title', 'Title');
        $this->addColumnDatetime('dateStarted', 'Service started');
        $this->addColumnDatetime('dateEnded', 'Service ended');
        $this->addColumnConst('status', 'Status', SystemServiceStatus::class);
    }
}

?>