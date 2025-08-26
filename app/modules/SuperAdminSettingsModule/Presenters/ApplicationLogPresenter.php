<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\ApplicationLogTypes;
use App\Core\DB\DatabaseRow;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ApplicationLogPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('ApplicationLogPresenter', 'Application log');
    }

    public function renderList() {}

    protected function createComponentAppLogGrid() {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->appLogRepository->composeQueryForApplicationLog();
        $qb->andWhere('type <> ?', [ApplicationLogTypes::STOPWATCH])
            ->orderBy('dateCreated', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'logId');

        $grid->setLimit(15);

        $col = $grid->addColumnText('message', 'Message');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');

            $el->title($value);

            if(strlen($value) > 50) {
                $el->text(substr($value, 0, 50) . '...');
            } else {
                $el->text($value);
            }

            return $el;
        };
        $col = $grid->addColumnText('method', 'Method');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');

            $el->title($value);

            if(strlen($value) > 50) {
                $el->text(substr($value, 0, 50) . '...');
            } else {
                $el->text($value);
            }

            return $el;
        };
        $grid->addColumnConst('type', 'Type', ApplicationLogTypes::class);
        $grid->addColumnDatetime('dateCreated', 'Date created');

        return $grid;
    }
}