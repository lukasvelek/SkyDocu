<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\ProcessStatus;
use App\Constants\Container\SystemGroups;
use App\Constants\ProcessColorCombos;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Helpers\GridHelper;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Filter;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;
use QueryBuilder\QueryBuilder;

class ProcessesPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');

        $this->setProcesses();
    }

    public function renderDashboard() {}

    public function renderList() {
        $links = [];

        if($this->groupManager->isUserMemberOfGroupTitle($this->getUserId(), SystemGroups::PROCESS_DESIGNERS)) {
            $links[] = LinkBuilder::createSimpleLink('New process', $this->createFullURL('Admin:ProcessEditor', 'form'), 'link');
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentProcessListGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->processRepository->composeQueryForAvailableProcesses();

        $grid->createDataSourceFromQueryBuilder($qb, 'processId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('description', 'Description');
        $grid->addColumnUser('userId', 'Author');
        $grid->addColumnConst('status', 'Status', ProcessStatus::class);
        $grid->addColumnBoolean('isEnabled', 'Enabled');
        $grid->addColumnText('version', 'Version');
        $grid->addColumnBoolean('isCustom', 'Is custom')
            ->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                $result = $this->processManager->isProcessCustom($row->processId);

                return GridHelper::createBooleanColumn($result);
            };

        $metadata = $grid->addAction('metadata');
        $metadata->setTitle('Metadata');
        $metadata->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->title == 'Invoice') {
                return true;
            }

            return false;
        };
        $metadata->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Metadata')
                ->class('grid-link')
                ->href($this->createFullURLString('Admin:ProcessMetadata', 'list', ['uniqueProcessId' => $row->uniqueProcessId]));

            return $el;
        };

        $copy = $grid->addAction('copy');
        $copy->setTitle('Copy');
        $copy->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->status != ProcessStatus::CURRENT) {
                return false;
            }

            try {
                $nextVersion = $this->processManager->getNextVersionForProcessId($row->processId, true);

                if($nextVersion !== null && $nextVersion->getStatus() == ProcessStatus::NEW) {
                    return false;
                }
            } catch(AException $e) {}

            return true;
        };
        $copy->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $params = [
                'processId' => $primaryKey,
                'uniqueProcessId' => $row->uniqueProcessId
            ];

            $el = HTML::el('a');
            $el->text('Copy')
                ->class('grid-link')
                ->href($this->createURLString('copyProcess', $params));

            return $el;
        };

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->status != ProcessStatus::NEW) {
                return false;
            }

            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $params = [
                'processId' => $primaryKey,
                'uniqueProcessId' => $row->uniqueProcessId
            ];

            try {
                $previousProcess = $this->processManager->getPreviousVersionForProcessId($primaryKey);

                if($previousProcess !== null) {
                    $params['oldProcessId'] = $previousProcess->processId;
                }
            } catch(AException $e) {}

            $el = HTML::el('a');
            $el->text('Edit')
                ->class('grid-link')
                ->href($this->createFullURLString('Admin:ProcessEditor', 'form', $params));

            return $el;
        };

        $disable = $grid->addAction('disable');
        $disable->setTitle('Disable');
        $disable->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->isEnabled == true;
        };
        $disable->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Disable')
                ->class('grid-link')
                ->href($this->createURLString('disableProcess', ['uniqueProcessId' => $row->uniqueProcessId, 'disable' => 1]));

            return $el;
        };

        $enable = $grid->addAction('enable');
        $enable->setTitle('Enable');
        $enable->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->isEnabled == false;
        };
        $enable->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Enable')
                ->class('grid-link')
                ->href($this->createURLString('disableProcess', ['uniqueProcessId' => $row->uniqueProcessId, 'disable' => 0]));

            return $el;
        };

        $grid->addFilter('isCustom', '0', [
                'No',
                'Yes'
            ])
            ->onSqlExecute[] = function(QueryBuilder &$qb, Filter $filter, mixed $value) {
                if($value == '0') {
                    $qb->andWhere($qb->getColumnNotInValues('status', [ProcessStatus::CURRENT, ProcessStatus::NEW, ProcessStatus::OLD]));
                } else {
                    $qb->andWhere($qb->getColumnInValues('status', [ProcessStatus::CURRENT, ProcessStatus::NEW, ProcessStatus::OLD]));
                }
            };

        return $grid;
    }

    public function handleDisableProcess() {
        $uniqueProcessId = $this->httpRequest->get('uniqueProcessId');
        $disable = $this->httpRequest->get('disable') == 1;

        try {
            $this->processRepository->beginTransaction(__METHOD__);

            if($disable) {
                $this->processManager->disableProcessByUniqueProcessId($uniqueProcessId);
                $this->flashMessage('Process successfully disabled.', 'success');
            } else {
                $this->processManager->enableProcessByUniqueProcessId($uniqueProcessId);
                $this->flashMessage('Process successfully enabled.', 'success');
            }

            $this->processRepository->commit($this->getUserId(), __METHOD__);
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not ' . ($disable ? 'disable' : 'enable') . ' process. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleCopyProcess() {
        $processId = $this->httpRequest->get('processId');
        $uniqueProcessId = $this->httpRequest->get('uniqueProcessId');

        try {
            $this->processRepository->beginTransaction(__METHOD__);

            [$newProcessId, $uniqueProcessId] = $this->processManager->createNewProcessFromExisting($processId);

            $this->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully created a new copy of process.', 'success');
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not create a new copy of process. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }
}

?>