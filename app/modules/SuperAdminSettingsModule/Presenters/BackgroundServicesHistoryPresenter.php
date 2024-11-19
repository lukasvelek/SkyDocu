<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\SystemServiceHistoryStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class BackgroundServicesHistoryPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('BackgroundServicesHistoryPresenter', 'Background services history');
    }

    public function renderList() {
        $this->template->links = $this->createBackFullUrl('SuperAdminSettings:BackgroundServices', 'list');
    }

    public function createComponentBgServiceHistoryGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->systemServicesRepository->composeQueryForServiceHistory($request->query['serviceId']), 'historyId');
        $grid->addQueryDependency('serviceId', $request->query['serviceId']);

        $col = $grid->addColumnConst('status', 'Status', SystemServiceHistoryStatus::class);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span')
                ->text($value)
                ->title($value);

            if($row->status == SystemServiceHistoryStatus::SUCCESS) {
                $el->style('color', 'green');
            } else {
                $el->style('color', 'red');
            }

            return $el;
        };

        $grid->addColumnDatetime('dateCreated', 'Date');

        return $grid;
    }
}

?>