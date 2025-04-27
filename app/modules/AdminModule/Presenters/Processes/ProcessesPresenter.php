<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
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

        return $grid;
    }
}

?>