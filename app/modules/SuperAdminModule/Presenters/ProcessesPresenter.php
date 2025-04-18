<?php

namespace App\Modules\SuperAdminModule;

use App\Constants\ProcessStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Helpers\LinkHelper;
use App\UI\FormBuilder2\JSON2FB;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ProcessesPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');
    }

    public function renderList() {
        $links = [
            LinkBuilder::createSimpleLink('New process', $this->createFullURL('SuperAdmin:ProcessEditor', 'form'), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentProcessesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->processRepository->composeQueryForProcesses();
        //$qb->orderBy('version', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'processId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnUser('userId', 'Author');
        $grid->addColumnConst('status', 'Status', ProcessStatus::class);

        $viewForm = $grid->addAction('viewForm');
        $viewForm->setTitle('View form');
        $viewForm->onCanRender[] = function() {
            return true;
        };
        $viewForm->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('View form')
                ->class('grid-link')
                ->href($this->createURLString('viewForm', ['processId' => $primaryKey]));

            return $el;
        };

        $addToDistribution = $grid->addAction('addToDistribution');
        $addToDistribution->setTitle('Add to distribution');
        $addToDistribution->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->status == ProcessStatus::NEW) {
                return true;
            }

            return false;
        };
        $addToDistribution->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Add to distribution')
                ->class('grid-link')
                ->href($this->createURLString('addToDistribution', ['processId' => $primaryKey]));

            return $el;
        };

        $removeFromDistribution = $grid->addAction('removeFromDistribution');
        $removeFromDistribution->setTitle('Remove from distribution');
        $removeFromDistribution->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->status == ProcessStatus::IN_DISTRIBUTION) {
                return true;
            }

            return false;
        };
        $removeFromDistribution->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Remove from distribution')
                ->class('grid-link')
                ->href($this->createURLString('removeFromDistribution', ['processId' => $primaryKey]));

            return $el;
        };

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Edit')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdmin:ProcessEditor', 'form', ['processId' => $primaryKey, 'uniqueProcessId' => $row->uniqueProcessId]));

            return $el;
        };

        return $grid;
    }

    public function renderViewForm() {
        $process = $this->app->processManager->getProcessById($this->httpRequest->get('processId'));

        $form = base64_decode($process->form);
        $form = new JSON2FB($this->componentFactory->getFormBuilder(), json_decode($form, true));
        $form->setViewOnly();

        $this->template->process_form = $form->render();
        $this->template->links = $this->createBackUrl('list');
    }

    public function handleAddToDistribution() {

    }

    public function handleRemoveFromDistribution() {

    }
}

?>