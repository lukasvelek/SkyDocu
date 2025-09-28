<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ProcessTransactionLogPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessTransactionLogPresenter', 'Process transaction log');

        $this->setProcesses();
    }

    public function renderList() {}

    protected function createComponentProcessTransactionLogGrid() {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->processInstanceRepository->composeQueryForProcessTransactionLog();
        $qb->orderBy('tsDateCreated', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'logId');

        $grid->addColumnText('message', 'Message');
        $col = $grid->addColumnText('instance', 'Process');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');

            try {
                $instance = $this->processInstanceManager->getProcessInstanceById($row->instanceId);

                $process = $this->processManager->getProcessById($instance->processId);

                $el->text($process->title);
            } catch(AException $e) {
                $el->text('#ERROR')
                    ->title($e->getMessage());
            }

            return $el;
        };

        return $grid;
    }
}