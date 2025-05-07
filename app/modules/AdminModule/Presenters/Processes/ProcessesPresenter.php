<?php

namespace App\Modules\AdminModule;

use App\Constants\ProcessColorCombos;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ProcessesPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');

        $this->setProcesses();
    }

    public function renderDashboard() {}

    public function renderList() {
        $this->template->links = '';
    }

    protected function createComponentProcessListGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->processRepository->composeQueryForAvailableProcesses();

        $grid->createDataSourceFromQueryBuilder($qb, 'processId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('description', 'Description');
        $grid->addColumnUser('userId', 'Author');

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
        $edit->onCanRender[] = function() {
            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Edit')
                ->class('grid-link')
                ->href($this->createFullURLString('Admin:Processes', 'editForm', ['uniqueProcessId' => $row->uniqueProcessId]));

            return $el;
        };

        return $grid;
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