<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Managers\EntityManager;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;
use App\UI\ListBuilder\ArrayRow;
use App\UI\ListBuilder\ListRow;

class DbAdminPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('DbAdminPresenter', 'Database administration');

        $this->setSystem();
    }

    public function handleList() {
        $links = [
            LinkBuilder::createSimpleLink('New database', $this->createURL('newDatabaseForm'), 'link')
        ];
        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentContainerDatabasesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->app->containerDatabaseRepository->composeQueryForContainerDatabases();

        $grid->createDataSourceFromQueryBuilder($qb, 'entryId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('name', 'Name');
        $grid->addColumnBoolean('isDefault', 'Is system');

        $truncate = $grid->addAction('truncate');
        $truncate->setTitle('Truncate');
        $truncate->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->isDefault == 0);
        };
        $truncate->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('truncateDatabaseForm', ['entryId' => $primaryKey]))
                ->text('Truncate');

            return $el;
        };

        $drop = $grid->addAction('drop');
        $drop->setTitle('Drop');
        $drop->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->isDefault == 0);
        };
        $drop->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('dropDatabaseForm', ['entryId' => $primaryKey]))
                ->text('Drop');

            return $el;
        };

        $tables = $grid->addAction('tables');
        $tables->setTitle('Tables');
        $tables->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if(APP_BRANCH == 'TEST') {
                return true;
            } else {
                return ($row->isDefault == 0);
            }
        };
        $tables->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('tableList', ['entryId' => $primaryKey]))
                ->text('Tables');

            return $el;
        };

        return $grid;
    }

    public function handleTruncateDatabaseForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->app->containerDatabaseRepository->beginTransaction(__METHOD__);

                $this->app->userAuth->authUser($fr->password);
                
                $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($this->httpRequest->get('entryId'));

                if($fr->title != $database->getTitle()) {
                    throw new GeneralException('Database titles do no match.');
                }

                $this->app->containerDatabaseManager->truncateDatabaseByEntryId($this->httpRequest->get('entryId'));

                $this->app->containerDatabaseRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Database truncated.', 'success');
            } catch(AException $e) {
                $this->app->containerDatabaseRepository->rollback(__METHOD__);

                $this->flashMessage('Could not truncate database. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        } else {
            $entryId = $this->httpRequest->get('entryId');

            $container = $this->app->containerManager->getContainerById($this->containerId);

            if($container->getDefaultDatabase()->getId() == $entryId) {
                $this->flashMessage('Could not truncate system database. Only custom databases can be truncated.', 'error', 10);

                $this->redirect($this->createURL('list'));
            }
        }
    }

    public function renderTruncateDatabaseForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentTruncateDatabaseForm(HttpRequest $request) {
        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($request->get('entryId'));

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('truncateDatabaseForm', ['entryId' => $database->getId()]));

        $form->addLabel('lbl_requirement1', 'Type in database title: \'' . $database->getTitle() . '\'.');
        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addPasswordInput('password', 'Your password:')
            ->setRequired();

        $form->addSubmit('Truncate');

        return $form;
    }

    public function handleDropDatabaseForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->app->containerDatabaseRepository->beginTransaction(__METHOD__);

                $this->app->userAuth->authUser($fr->password);
                
                $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($this->httpRequest->get('entryId'));

                if($fr->title != $database->getTitle()) {
                    throw new GeneralException('Database titles do no match.');
                }

                $this->app->containerDatabaseManager->dropDatabaseByEntryId($this->httpRequest->get('entryId'));

                $this->app->containerDatabaseRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Database dropped.', 'success');
            } catch(AException $e) {
                $this->app->containerDatabaseRepository->rollback(__METHOD__);

                $this->flashMessage('Could not drop database. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        } else {
            $entryId = $this->httpRequest->get('entryId');

            $container = $this->app->containerManager->getContainerById($this->containerId);

            if($container->getDefaultDatabase()->getId() == $entryId) {
                $this->flashMessage('Could not drop system database. Only custom databases can be dropped.', 'error', 10);

                $this->redirect($this->createURL('list'));
            }
        }
    }

    public function renderDropDatabaseForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentDropDatabaseForm(HttpRequest $request) {
        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($request->get('entryId'));

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('dropDatabaseForm', ['entryId' => $database->getId()]));

        $form->addLabel('lbl_requirement1', 'Type in database title: \'' . $database->getTitle() . '\'.');
        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addPasswordInput('password', 'Your password:')
            ->setRequired();

        $form->addSubmit('Drop');

        return $form;
    }

    public function handleNewDatabaseForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->app->containerDatabaseRepository->beginTransaction(__METHOD__);

                $this->app->containerDatabaseManager->insertNewContainerDatabase($this->containerId, $fr->name, $fr->title, $fr->description);

                $this->app->containerDatabaseRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Database created.', 'success');
            } catch(AException $e) {
                $this->app->containerDatabaseRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create database. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewDatabaseForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentNewDatabaseForm(HttpRequest $request) {
        $name = $this->app->containerManager->generateContainerDatabaseName($this->containerId);
        
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newDatabaseForm'));

        $form->addTextInput('title', 'Title:')
            ->setRequired();
        
        $form->addTextArea('description', 'Description:')
            ->setRequired();

        $form->addTextInput('name', 'Name:')
            ->setReadonly()
            ->setValue($name);

        $form->addSubmit('Create');

        return $form;
    }

    public function renderTableList() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentDatabaseTablesGrid(HttpRequest $request) {
        $entryId = $request->get('entryId');
        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);
        $tables = $this->app->dbManager->getAllTablesInDatabase($database->getName());

        $data = [];
        $i = 0;
        foreach($tables as $row) {
            $data[$i]['table'] = $row['Tables_in_' . strtolower($database->getName())];

            $i++;
        }

        $list = $this->componentFactory->getListBuilder();

        $list->setDataSource($data);

        $list->addColumnText('table', 'Table');
        
        $scheme = $list->addAction('scheme');
        $scheme->setTitle('Scheme');
        $scheme->onCanRender[] = function() {
            return true;
        };
        $scheme->onRender[] = function(mixed $primaryKey, ArrayRow $row, ListRow $_row, HTML $html) use ($entryId, $data) {
            $index = substr($primaryKey, 4);
            $table = $data[$index]['table'];
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('tableSchemeList', ['entryId' => $entryId, 'table' => $table]))
                ->text('Scheme');

            return $el;
        };

        return $list;
    }

    public function handleTableSchemeList() {}
}

?>