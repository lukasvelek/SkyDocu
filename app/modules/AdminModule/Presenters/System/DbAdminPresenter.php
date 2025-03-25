<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\ContainerCreationHelper;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;
use App\UI\ListBuilder\ArrayRow;
use App\UI\ListBuilder\ListAction;
use App\UI\ListBuilder\ListRow;

class DbAdminPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('DbAdminPresenter', 'Database administration');

        $this->setSystem();
    }

    public function renderList() {
        $this->template->links = LinkBuilder::createSimpleLink('New database', $this->createURL('newDatabaseForm'), 'link');
    }

    protected function createComponentContainerDatabasesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->app->containerDatabaseRepository->composeQueryForContainerDatabases();
        $qb->andWhere('containerId = ?', [$this->containerId]);

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
            if($entryId === null) {
                throw new RequiredAttributeIsNotSetException('entryId');
            }

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

                $this->app->containerDatabaseManager->dropDatabaseByEntryId($this->containerId, $this->httpRequest->get('entryId'));

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
        $entryId = $this->httpRequest->get('entryId');
        if($entryId === null) {
            throw new RequiredAttributeIsNotSetException('entryId');
        }
        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);
        $path = $database->getName();

        $links = [
            $this->createBackUrl('list')
        ];

        if($database->isDefault() === false) {
            $links[] = LinkBuilder::createSimpleLink('New table', $this->createURL('newTableForm', ['entryId' => $entryId]), 'link');
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);
        $this->template->path = $path;
    }

    protected function createComponentDatabaseTablesList(HttpRequest $request) {
        $entryId = $request->get('entryId');
        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);
        
        $data = [];
        if($database->isDefault()) {
            $tables = $this->app->dbManager->getAllTablesInDatabase($database->getName());
            
            $i = 0;
            foreach($tables as $row) {
                $data[$i]['table'] = $row['Tables_in_' . strtolower($database->getName())];

                $i++;
            }
        } else {
            $qb = $this->app->containerDatabaseRepository->composeQueryForContainerDatabaseTables();
            $qb->andWhere('databaseId = ?', [$entryId])
                ->execute();

            $i = 0;
            while($row = $qb->fetchAssoc()) {
                $data[$i]['table'] = $row['name'];
                $data[$i]['isCreated'] = $row['isCreated'];
                $data[$i]['entryId'] = $row['entryId'];

                $i++;
            }
        }

        $list = $this->componentFactory->getListBuilder();

        $list->setListName('DatabaseTablesList');
        $list->setDataSource($data);

        $list->addColumnText('table', 'Table');

        if(!$database->isDefault()) {
            $list->addColumnBoolean('isCreated', 'Is created');

            $create = $list->addAction('create');
            $create->setTitle('Create');
            $create->onCanRender[] = function(ArrayRow $row, ListRow $_row, ListAction &$action) use ($entryId) {
                return $this->app->containerDatabaseManager->canContainerDatabaseTableBeCreated($this->containerId, $entryId, $row->entryId);
            };
            $create->onRender[] = function(mixed $primaryKey, ArrayRow $row, ListRow $_row, HTML $html) use ($entryId, $data) {
                $el = HTML::el('a')
                    ->class('grid-link')
                    ->href($this->createURLString('createTableScheme', ['entryId' => $entryId, 'tableId' => $row->entryId]))
                    ->text('Create')
                ;

                return $el;
            };

            $showData = $list->addAction('showData');
            $showData->setTitle('Data');
            $showData->onCanRender[] = function(ArrayRow $row, ListRow $_row, ListAction &$action) use ($entryId) {
                return !$this->app->containerDatabaseManager->canContainerDatabaseTableBeCreated($this->containerId, $entryId, $row->entryId);
            };
            $showData->onRender[] = function(mixed $primaryKey, ArrayRow $row, ListRow $_row, HTML $html) use ($entryId) {
                $el = HTML::el('a')
                    ->class('grid-link')
                    ->text('Data')
                    ->href($this->createURLString('tableDataList', ['entryId' => $entryId, 'tableId' => $row->entryId, 'table' => $row->table]))
                ;

                return $el;
            };
        }
        
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

    public function renderTableSchemeList() {
        $entryId = $this->httpRequest->get('entryId');
        if($entryId === null) {
            throw new RequiredAttributeIsNotSetException('entryId');
        }
        $table = $this->httpRequest->get('table');
        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);
        $path = $database->getName() . ' > ' . $table  . ' > Schema';

        $links = [
            $this->createBackUrl('tableList', ['entryId' => $this->httpRequest->get('entryId')])
        ];

        if(!$database->isDefault()) {
            $tableRow = $this->app->containerDatabaseManager->getContainerDatabaseTableByName($this->containerId, $entryId, $table);
            
            if(!$tableRow->isCreated) {
                $links[] = LinkBuilder::createSimpleLink('New column', $this->createURL('newTableColumnForm', ['entryId' => $entryId, 'tableId' => $tableRow->entryId, 'table' => $table]), 'link');
            }
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);
        $this->template->path = $path;
    }

    protected function createComponentDatabaseTableSchemeList(HttpRequest $request) {
        $entryId = $request->get('entryId');
        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);
        $table = $request->get('table');

        $data = [];
        if($database->isDefault()) {
            $scheme = ContainerCreationHelper::getContainerTableDefinitions();

            if(array_key_exists($table, $scheme)) {
                $tableScheme = $scheme[$table];

                $i = 0;
                foreach($tableScheme as $name => $definition) {
                    $data[$i]['column'] = $name;
                    $data[$i]['definition'] = $definition;

                    $i++;
                }
            }
        } else {
            $tableRow = $this->app->containerDatabaseManager->getContainerDatabaseTableByName($this->containerId, $entryId, $table);
            $qb = $this->app->containerDatabaseRepository->composeQueryForContainerDatabaseTableColumns();
            $qb->andWhere('containerId = ?', [$this->containerId])
                ->andWhere('databaseId = ?', [$entryId])
                ->andWhere('tableId = ?', [$tableRow->entryId])
                ->execute();

            $i = 0;
            while($row = $qb->fetchAssoc()) {
                $data[$i]['column'] = $row['name'];
                $data[$i]['definition'] = $row['definition'];

                $i++;
            }
        }

        $list = $this->componentFactory->getListBuilder();

        $list->setListName('DatabaseTableSchemeList');
        $list->setDataSource($data);

        $list->addColumnText('column', 'Column');
        $list->addColumnText('definition', 'Definition');

        return $list;
    }

    public function handleNewTableForm(?FormRequest $fr = null) {
        $entryId = $this->httpRequest->get('entryId');
        if($entryId === null) {
            throw new RequiredAttributeIsNotSetException('entryId');
        }

        if($fr !== null) {
            try {
                $this->app->containerDatabaseRepository->beginTransaction(__METHOD__);

                $name = str_replace(' ', '_', $fr->name);
                $name = str_replace('-', '_', $name);
                $name = strtolower($name);

                $this->app->containerDatabaseManager->insertNewContainerDatabaseTable($this->containerId, $entryId, $name);

                $this->app->containerDatabaseRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Table created successfully.', 'success');
            } catch(AException $e) {
                $this->app->containerDatabaseRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create table. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('tableList', ['entryId' => $entryId]));
        }
    }

    public function renderNewTableForm() {
        $entryId = $this->httpRequest->get('entryId');
        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);
        $path = $database->getName() . ' > New table';

        $this->template->links = $this->createBackUrl('tableList', ['entryId' => $entryId]);
        $this->template->path = $path;
    }

    protected function createComponentNewTableForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newTableForm', ['entryId' => $request->get('entryId')]));

        $form->addTextInput('name', 'Name:')
            ->setRequired();
        $form->addSubmit('Create');

        return $form;
    }

    public function handleNewTableColumnForm(?FormRequest $fr = null) {
        $entryId = $this->httpRequest->get('entryId');
        if($entryId === null) {
            throw new RequiredAttributeIsNotSetException('entryId');
        }
        $tableId = $this->httpRequest->get('tableId');
        if($tableId === null) {
            throw new RequiredAttributeIsNotSetException('tableId');
        }
        $table = $this->httpRequest->get('table');
        if($table === null) {
            throw new RequiredAttributeIsNotSetException('table');
        }

        if($fr !== null) {
            try {
                $this->app->containerDatabaseRepository->beginTransaction(__METHOD__);

                $this->app->containerDatabaseManager->insertNewContainerDatabaseTableColumn(
                    $this->containerId,
                    $entryId,
                    $tableId,
                    $fr->name,
                    $fr->title,
                    $fr->definition
                );

                $this->app->containerDatabaseRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('New table column created successfully.', 'success');
            } catch(AException $e) {
                $this->app->containerDatabaseRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create new table column. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('tableSchemeList', ['entryId' => $entryId, 'table' => $table]));
        }
    }

    public function renderNewTableColumnForm() {
        $entryId = $this->httpRequest->get('entryId');
        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);
        $table = $this->httpRequest->get('table');
        $path = $database->getName() . ' > ' . $table . ' > New column';

        $this->template->links = $this->createBackUrl('tableSchemeList', ['entryId' => $entryId, 'table' => $table]);
        $this->template->path = $path;
    }

    protected function createComponentNewTableColumnForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newTableColumnForm', ['entryId' => $request->get('entryId'), 'table' => $request->get('table'), 'tableId' => $request->get('tableId')]));

        $form->addTextInput('name', 'Name:')
            ->setRequired();
        $form->addTextInput('title', 'Title:')
            ->setRequired();
        $form->addTextArea('definition', 'Definition:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }

    public function handleCreateTableScheme() {
        $entryId = $this->httpRequest->get('entryId');
        $tableId = $this->httpRequest->get('tableId');

        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);
        $tableRow = $this->app->containerDatabaseManager->getContainerDatabaseTableById($this->containerId, $entryId, $tableId);

        $qb = $this->app->containerDatabaseRepository->composeQueryForContainerDatabaseTableColumns();
        $qb->andWhere('containerId = ?', [$this->containerId])
            ->andWhere('databaseId = ?', [$entryId])
            ->andWhere('tableId = ?', [$tableId])
            ->execute();

        $columns = [];
        while($row = $qb->fetchAssoc()) {
            $columns[$row['name']] = $row['definition'];
        }

        try {
            $this->app->containerDatabaseRepository->beginTransaction(__METHOD__);

            $this->app->dbManager->createTable($tableRow->name, $columns, $database->getName());

            $this->app->containerDatabaseManager->updateContainerDatabaseTable($tableId, [
                'isCreated' => '1'
            ]);

            $this->app->containerDatabaseRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Table scheme successfully created.', 'success');
        } catch(AException $e) {
            $this->app->containerDatabaseRepository->rollback(__METHOD__);

            $this->flashMessage('Could not create table scheme. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('tableList', ['entryId' => $entryId]));
    }

    public function renderTableDataList() {
        $entryId = $this->httpRequest->get('entryId');
        if($entryId === null) {
            throw new RequiredAttributeIsNotSetException('entryId');
        }
        $table = $this->httpRequest->get('table');
        if($table === null) {
            throw new RequiredAttributeIsNotSetException('table');
        }
        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);
        $path = $database->getName() . ' > ' . $table . ' > Data';

        $this->template->links = $this->createBackUrl('tableList', ['entryId' => $entryId]);
        $this->template->path = $path;
    }

    protected function createComponentTableDataGrid(HttpRequest $request) {
        $entryId = $request->get('entryId');
        $tableId = $request->get('tableId');
        $table = $request->get('table');

        $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);

        $columnsQb = $this->app->containerDatabaseRepository->composeQueryForContainerDatabaseTableColumns()
            ->andWhere('tableId = ?', [$tableId])
            ->andWhere('databaseId = ?', [$entryId])
            ->execute();

        $columns = [];
        $primaryKey = null;
        while($row = $columnsQb->fetchAssoc()) {
            $row = DatabaseRow::createFromDbRow($row);

            if($primaryKey === null) {
                $primaryKey = $row->name;
            }

            $columns[$row->name] = $row->title;
        }

        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->app->dbManager->getQbWithConnectionToDifferentDatabase($database->getName(), __METHOD__);

        $qb->select(['*'])
            ->from($table);

        $grid->createDataSourceFromQueryBuilder($qb, $primaryKey);
        $grid->addQueryDependency('entryId', $entryId);
        $grid->addQueryDependency('tableId', $tableId);
        $grid->addQueryDependency('table', $table);

        foreach($columns as $name => $label) {
            $grid->addColumnText($name, $label);
        }

        return $grid;
    }
}

?>