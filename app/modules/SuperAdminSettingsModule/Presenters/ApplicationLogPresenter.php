<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\ApplicationLogLevels;
use App\Constants\ApplicationLogTypes;
use App\Core\DB\DatabaseRow;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ApplicationLogPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('ApplicationLogPresenter', 'Application log');
    }

    public function renderList() {
        $links = [];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentApplicationLogGrid() {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->appLogRepository->composeQueryForApplicationLog();

        $qb->andWhere('contextId IN (SELECT contextId FROM application_log GROUP BY contextId ORDER BY dateCreated)')
            ->andWhere('userId NOT IN (SELECT userId FROM users WHERE fullname = \'service_user\')')
        ;

        $grid->createDataSourceFromQueryBuilder($qb, 'logId');

        $grid->setLimit(10);

        $grid->addColumnText('caller', 'Caller');
        $grid->addColumnUser('userId', 'User');

        $col = $grid->addColumnText('message', 'Message');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            if(strlen($value) > 50) {
                $value = substr($value, 0, 50) . '...';
            }

            return $value;
        };
        
        $grid->addColumnConst('type', 'Type', ApplicationLogTypes::class);
        $grid->addColumnConst('level', 'Level', ApplicationLogLevels::class);

        return $grid;
    }
}