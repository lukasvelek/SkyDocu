<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\JobQueueProcessingHistoryTypes;
use App\Constants\JobQueueStatus;
use App\Constants\JobQueueTypes;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Filter;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use QueryBuilder\QueryBuilder;

class JobQueuePresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('JobQueuePresenter', 'Job queue');
    }

    public function renderList() {
        $links = [
            $this->createBackFullUrl('SuperAdminSettings:BackgroundServices', 'list')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentJobQueueGrid() {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->jobQueueRepository->commonComposeQuery();
        $qb->orderBy('dateModified', 'DESC');
    
        $grid->createDataSourceFromQueryBuilder($qb, 'jobId');

        $grid->addColumnConst('type', 'Type', JobQueueTypes::class);
        $grid->addColumnConst('status', 'Status', JobQueueStatus::class);
        $grid->addColumnText('statusMessage', 'Status message');
        $grid->addColumnDatetime('dateCreated', 'Date created');
        $grid->addColumnDatetime('dateModified', 'Date modified');
        $col = $grid->addColumnText('isScheduled', 'Scheduled now');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');

            if($row->status == JobQueueStatus::NEW && strtotime($row->executionDate) <= time()) {
                // yes
                $el->text('&check;')
                    ->style('color', 'green')
                    ->style('background-color', 'lightgreen')
                    ->style('border-radius', '12px')
                    ->style('padding', '5px');
            } else {
                // no
                $el->text('&times;')
                    ->style('color', 'red')
                    ->style('background-color', 'pink')
                    ->style('border-radius', '12px')
                    ->style('padding', '5px');
            }

            return $el;
        };

        $history = $grid->addAction('history');
        $history->setTitle('History');
        $history->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->status != JobQueueStatus::NEW;
        };
        $history->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('History')
                ->href($this->createURLString('historyList', ['jobId' => $primaryKey]))
                ->class('grid-link');
            
            return $el;
        };

        $grid->addFilter('status', JobQueueStatus::NEW, JobQueueStatus::getAll());
        $grid->addFilter('type', JobQueueTypes::DELETE_CONTAINER, JobQueueTypes::getAll());
        $filter = $grid->addFilter('isScheduled', 0, ['No', 'Yes']);
        $filter->onSqlExecute[] = function(QueryBuilder &$qb, Filter $filter, mixed $value) {
            if($value == '0') {
                $qb->andWhere('(status <> ? AND executionDate < ?)', [JobQueueStatus::NEW, DateTime::now()]);
            } else {
                $qb->andWhere('(status = ? AND executionDate <= ?)', [JobQueueStatus::NEW, DateTime::now()]);
            }
        };

        return $grid;
    }

    public function renderHistoryList() {
        $links = [
            $this->createBackUrl('list')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentJobHistoryGrid() {
        $jobId = $this->httpRequest->get('jobId');

        $grid = $this->componentFactory->getGridBuilder();
        $grid->addQueryDependency('jobId', $jobId);

        $qb = $this->app->jobQueueProcessingHistoryRepository->composeQueryForJobId($jobId);
        $qb->orderBy('dateCreated', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'entryId');

        $grid->addColumnConst('type', 'Type', JobQueueProcessingHistoryTypes::class);
        $grid->addColumnText('description', 'Description');
        $grid->addColumnDatetime('dateCreated', 'Date created');

        return $grid;
    }
}

?>