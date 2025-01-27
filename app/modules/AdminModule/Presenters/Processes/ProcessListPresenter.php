<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\StandaloneProcesses;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ProcessListPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessListPresenter', 'Process list');

        $this->setProcesses();
    }

    public function renderList() {}

    protected function createComponentProcessGrid() {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->processRepository->composeQueryForProcessTypes();

        $grid->createDataSourceFromQueryBuilder($qb, 'typeId');

        $col = $grid->addColumnText('typeKey', 'Title');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            return $row->title;
        };

        $grid->addColumnText('description', 'Description');
        $grid->addColumnBoolean('isEnabled', 'Enabled');

        $grid->addFilter('typeKey', null, StandaloneProcesses::getAll());
        $grid->addFilter('isEnabled', null, ['No', 'Yes']);

        $switch = $grid->addAction('switch');
        $switch->setTitle('Enable / Disable');
        $switch->onCanRender[] = function() {
            return true;
        };
        $switch->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->class('grid-link');

            $url = [
                'type' => $row->typeKey
            ];

            if($row->isEnabled == true) {
                $el->text('Disable');
                $url['operation'] = 'disable';
            } else {
                $el->text('Enable');
                $url['operation'] = 'enable';
            }

            $el->href($this->createURLString('switch', $url));

            return $el;
        };

        return $grid;
    }

    public function handleSwitch() {
        $type = $this->httpRequest->query('type');
        if($type === null) {
            throw new RequiredAttributeIsNotSetException('type');
        }
        $operation = $this->httpRequest->query('operation');
        if($operation === null) {
            throw new RequiredAttributeIsNotSetException('operation');
        }

        $data = [];
        if($operation == 'enable') {
            $data['isEnabled'] = '1';
        } else {
            $data['isEnabled'] = '0';
        }

        try {
            $this->processRepository->beginTransaction(__METHOD__);

            $this->standaloneProcessManager->updateProcessType($type, $data);

            $this->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Process ' . ($operation == 'enable' ? 'enabled' : 'disabled') . ' successfully.', 'success');
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not ' . ($operation == 'enable' ? 'enable' : 'disable')  . ' process. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }
}

?>