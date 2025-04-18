<?php

namespace App\Modules\SuperAdminModule;

use App\Constants\ProcessStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Helpers\LinkHelper;
use App\UI\FormBuilder2\JSON2FB;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ProcessesPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');
    }

    public function renderList() {
        $links = [
            LinkBuilder::createSimpleLink('New process', $this->createFullURL('SuperAdmin:NewProcessEditor', 'form'), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentProcessesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->processRepository->composeQueryForProcesses();

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
}

?>