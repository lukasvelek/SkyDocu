<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\ProcessStatus;
use App\Constants\Container\SystemGroups;
use App\Constants\ProcessColorCombos;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

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

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->status == ProcessStatus::CUSTOM;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Edit')
                ->class('grid-link')
                ->href($this->createFullURLString('Admin:Processes', 'editForm', ['uniqueProcessId' => $row->uniqueProcessId]));

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

    public function handleEditForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->processRepository->beginTransaction(__METHOD__);

                $process = $this->processManager->getLastProcessForUniqueProcessId($this->httpRequest->get('uniqueProcessId'));

                $this->processRepository->updateProcess($process->processId, ['colorCombo' => $fr->colorCombo]);

                $this->processRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Successfully saved process.', 'success');
            } catch(AException $e) {
                $this->processRepository->rollback(__METHOD__);

                $this->flashMessage('Could not save process. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderEditForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentEditProcessForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('editForm', ['uniqueProcessId' => $request->get('uniqueProcessId')]));

        $process = $this->processManager->getLastProcessForUniqueProcessId($request->get('uniqueProcessId'));

        $colors = [];
        foreach(ProcessColorCombos::getAll() as $key => $value) {
            $color = [
                'value' => $key,
                'text' => $value
            ];

            if($process->colorCombo == $key) {
                $color['selected'] = 'selected';
            }

            $colors[] = $color;
        }

        $form->addSelect('colorCombo', 'Color:')
            ->setRequired()
            ->addRawOptions($colors);

        $form->addSubmit('Save');

        return $form;
    }
}

?>