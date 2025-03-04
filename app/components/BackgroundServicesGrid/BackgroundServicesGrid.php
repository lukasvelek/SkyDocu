<?php

namespace App\Components\BackgroundServicesGrid;

use App\Constants\SystemServiceStatus;
use App\Core\Application;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Helpers\BackgroundServiceScheduleHelper;
use App\Helpers\GridHelper;
use App\Repositories\SystemServicesRepository;
use App\UI\GridBuilder2\Cell;
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

    private ?string $serviceId;

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

        $this->serviceId = null;
    }

    public function setServiceId(string $serviceId) {
        $this->serviceId = $serviceId;
    }

    public function prerender() {
        $this->createDataSource();

        $this->fetchDataFromDb();

        $this->appendSystemMetadata();
        
        if($this->serviceId === null) {
            $this->appendNextRunColumn();
        }

        $this->appendActions();

        $this->setup();

        parent::prerender();
    }

    public function createDataSource() {
        $qb = $this->systemServicesRepository->composeQueryForServices();
        
        if($this->serviceId === null) {
            $qb->andWhere('parentServiceId IS NULL');
        } else {
            $qb->andWhere('parentServiceId = ?', [$this->serviceId]);
        }

        $this->createDataSourceFromQueryBuilder($qb, 'serviceId');
    }

    /**
     * Sets up the grid
     */
    private function setup() {
        $this->setGridName(GridHelper::GRID_BACKGROUND_SERVICES);

        $this->addQuickSearch('title', 'Title');
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
            return ($row->dateStarted !== null && $row->dateEnded !== null);
        };
        $history->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdminSettings:BackgroundServicesHistory', 'list', ['serviceId' => $primaryKey]))
                ->text('History');

            return $el;
        };

        $children = $this->addAction('children');
        $children->setTitle('Children');
        $children->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return $this->systemServicesRepository->getChildrenCountForServiceId($row->serviceId) > 0;
        };
        $children->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdminSettings:BackgroundServices', 'list', ['serviceId' => $primaryKey]))
                ->text('Children');

            return $el;
        };

        $edit = $this->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdminSettings:BackgroundServices', 'editForm', ['serviceId' => $primaryKey]))
                ->text('Edit');

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
        $this->addColumnBoolean('isEnabled', 'Is enabled');
    }

    private function appendNextRunColumn() {
        $col = $this->addColumnText('nextRun', 'Next run');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $schedule = json_decode($row->schedule, true);

            $days = $schedule['schedule']['days'];
            $time = $schedule['schedule']['time'] . ':00';

            $todayShortcut = strtolower(date('D'));

            if(in_array($todayShortcut, explode(';', $days))) {
                $pos = array_search(strtolower($todayShortcut), explode(';', $days));

                if(($pos + 1) == count(explode(';', $days))) {
                    // last
                    $next = explode(';', $days)[0];
                } else {
                    // not last
                    $next = explode(';', $days)[$pos + 1];
                }

                $daysArr = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

                $t = '';
                $todayIndex = array_search(strtolower($todayShortcut), $daysArr);
                $nextIndex = array_search(strtolower($next), $daysArr);
                if($todayIndex < $nextIndex) {
                    $diff = $nextIndex - $todayIndex;
                } else {
                    $diff = 7 - $todayIndex + $nextIndex; // count until the end of the week plus index of the next day
                }

                $_text = new DateTime();
                $_text->modify('+' . $diff . 'd');
                $_text->format('d.m.Y');
                $text = $_text->getResult() . ' ' . $time;

                $_title = new DateTime();
                $_title->modify('+' . $diff . 'd');
                $_title->format('Y-m-d');
                $title = $_title->getResult() . ' ' . $time . ':00';

                $el = HTML::el('span')
                    ->text($text)
                    ->title($title);

                return $el;
            } else {
                return '-';
            }
        };
    }
    
    public function actionQuickSearch(): JsonResponse {
        $this->quickSearchQuery = $this->httpRequest->post('query');

        $this->prerender();

        return parent::actionQuickSearch();
    }

    public function actionGetSkeleton(): JsonResponse {
        $this->prerender();

        return parent::actionGetSkeleton();
    }
}

?>