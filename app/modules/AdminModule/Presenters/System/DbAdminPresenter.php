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

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->isDefault == 0);
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('deleteDatabaseForm', ['entryId' => $primaryKey]))
                ->text('Delete');

            return $el;
        };

        return $grid;
    }

    public function handleDropDatabaseForm() {
        // todo: implement confirmation form
    }

    public function handleDeleteDatabaseForm() {
        // todo: implement confirmation form
    }

    public function handleNewDatabaseForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->app->containerDatabaseRepository->beginTransaction(__METHOD__);

                $entryId = $this->app->entityManager->generateEntityId(EntityManager::CONTAINER_DATABASES);

                if(!$this->app->containerDatabaseRepository->insertNewContainerDatabase($entryId, $this->containerId, $fr->name, $fr->title, $fr->description)) {
                    throw new GeneralException('Database error.');
                }

                $this->app->containerDatabaseRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Database created.', 'success');
            } catch(AException $e) {
                $this->app->containerDatabaseRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create database. Reason: ' . $e->getMessage(), 'error');
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
}

?>