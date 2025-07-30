<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\ExternalSystemLogActionTypes;
use App\Constants\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ExternalSystemsPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('ExternalSystemsPresenter', 'External systems');
    }

    public function renderList() {
        $links = [
            LinkBuilder::createSimpleLink('Global log', $this->createURL('globalLogList'), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentExternalSystemsGrid() {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->externalSystemsRepository->composeQueryForExternalSystems();

        $grid->createDataSourceFromQueryBuilder($qb, 'systemId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('description', 'Description');
        
        $col = $grid->addColumnText('containerId', 'Container');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            if($row->containerId === null) {
                return '-';
            } else {
                try {
                    $container = $this->app->containerManager->getContainerById($row->containerId);

                    $el = HTML::el('span');

                    $el->title($row->containerId);
                    $el->text($container->getTitle());

                    return $el;
                } catch(AException $e) {
                    return '-';
                }
            }
        };

        $log = $grid->addAction('log');
        $log->setTitle('Log');
        $log->onCanRender[] = function() {
            return true;
        };
        $log->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');

            $el->class('grid-link')
                ->href($this->createURLString('systemLog', ['systemId' => $primaryKey]))
                ->text('Log');

            return $el;
        };

        $grid->addColumnBoolean('isEnabled', 'Enabled');

        return $grid;
    }

    public function renderSystemLog() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentExternalSystemLogGrid() {
        $systemId = $this->httpRequest->get('systemId');

        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->externalSystemsLogRepository->composeQueryForLogEntriesForSystem($systemId);
        $qb->orderBy('dateCreated', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'entryId');
        $grid->addQueryDependency('systemId', $systemId);

        $grid->addColumnText('message', 'Message');
        $grid->addColumnConst('actionType', 'Action', ExternalSystemLogActionTypes::class);
        $grid->addColumnConst('objectType', 'Object', ExternalSystemLogObjectTypes::class);
        $grid->addColumnDatetime('dateCreated', 'Date created');

        return $grid;
    }

    public function renderGlobalLogList() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentExternalSystemsGlobalLogGrid() {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->externalSystemsLogRepository->composeQueryForLogEntries();
        $qb->orderBy('dateCreated', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'entryId');

        $grid->addColumnText('message', 'Message');
        $grid->addColumnConst('actionType', 'Action', ExternalSystemLogActionTypes::class);
        $grid->addColumnConst('objectType', 'Object', ExternalSystemLogObjectTypes::class);
        $grid->addColumnDatetime('dateCreated', 'Date created');

        return $grid;
    }
}