<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class GroupsPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('GroupsPresenter', 'Groups');
    }

    public function renderList() {
        $this->template->links = [];
    }

    protected function createComponentGroupsGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->groupRepository->composeQueryForGroups(), 'groupId');

        $grid->addColumnText('title', 'Title');
        $col = $grid->addColumnText('containerId', 'Container');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');

            if($value === null) {
                return $value;
            } else {
                try {
                    $container = $this->app->containerManager->getContainerById($value);
    
                    $el->title($container->title)
                        ->text($container->title);
                } catch(AException $e) {
                    $el->title($e->getMessage())
                        ->text('?')
                        ->style('color', 'red')
                    ;
                }
            }

            return $el;
        };
        $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
            if($value === null) {
                return $value;
            } else {
                try {
                    $container = $this->app->containerManager->getContainerById($value);
    
                    return $container->title;
                } catch(AException $e) {
                    return $value;
                }
            }
        };

        $users = $grid->addAction('users');
        $users->setTitle('Users');
        $users->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return true;
        };
        $users->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Users')
                ->href($this->createURLString('listUsers', ['groupId' => $primaryKey]))
                ->class('grid-link');

            return $el;
        };

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            if($row->title == 'superadministrators') {
                return false;
            } else {
                return true;
            }
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Edit')
                ->href($this->createFullURLString('SuperAdminSettings:GroupsSettings', 'editGroup', ['groupId' => $primaryKey]))
                ->class('grid-link');

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            if($row->title == 'superadministrators') {
                return false;
            }

            if($row->containerId !== null) {
                return false;
            }

            return true;
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Delete')
                ->href($this->createFullURLString('SuperAdminSettings:GroupsSettings', 'editGroup', ['groupId' => $primaryKey]))
                ->class('grid-link');

            return $el;
        };

        return $grid;
    }

    public function handleListUsers() {
        $groupId = $this->httpGet('groupId', true);

        try {
            $group = $this->app->groupManager->getGroupById($groupId);
        } catch(AException $e) {
            $this->flashMessage('This group does not exist.', 'error', 10);
            $this->redirect($this->createURL('list'));
        }

        $this->saveToPresenterCache('groupName', $group->title);
    }

    public function renderListUsers() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link')
        ];
        $this->template->group_name = $this->loadFromPresenterCache('groupName');
    }

    protected function createComponentGroupUsersGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->groupMembershipRepository->composeQueryForGroupUsers($request->query['groupId']), 'groupUserId');

        $grid->addColumnUser('userId', 'User');

        return $grid;
    }
}

?>