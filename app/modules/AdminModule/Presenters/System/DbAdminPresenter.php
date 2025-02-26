<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class DbAdminPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('DbAdminPresenter', 'Database administration');

        $this->setSystem();
    }

    public function renderList() {}

    protected function createComponentContainerDatabasesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->app->containerDatabaseRepository->composeQueryForContainerDatabases();

        $grid->createDataSourceFromQueryBuilder($qb, 'entryId');

        $grid->addColumnText('name', 'Name');
        $grid->addColumnBoolean('isDefault', 'Is system');

        $drop = $grid->addAction('drop');
        $drop->setTitle('Drop');
        $drop->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->isDefault == 0);
        };
        $drop->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('dropDatabase', ['entryId' => $primaryKey]))
                ->text('Drop');

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->isDefault == 0);
        };
        $drop->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('deleteDatabase', ['entryId' => $primaryKey]))
                ->text('Delete');

            return $el;
        };

        return $grid;
    }

    public function handleDropDatabase() {
        // todo: implement confirmation form
    }

    public function handleDeleteDatabase() {
        // todo: implement confirmation form
    }
}

?>