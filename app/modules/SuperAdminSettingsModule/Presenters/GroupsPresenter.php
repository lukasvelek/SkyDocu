<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Core\AjaxRequestBuilder;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
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
        $grid = $this->componentFactory->getGridBuilder();

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

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link'),
            LinkBuilder::createSimpleLink('Add user', $this->createURL('addUserForm', ['groupId' => $groupId]), 'link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderListUsers() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->group_name = $this->loadFromPresenterCache('groupName');
    }

    protected function createComponentGroupUsersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->groupMembershipRepository->composeQueryForGroupUsers($request->query['groupId']), 'groupUserId');

        $grid->addColumnUser('userId', 'User');
        $grid->addColumnDatetime('dateCreated', 'Member since');

        $groupUsers = $this->app->groupManager->getGroupUsersForGroupId($request->query['groupId']);

        $remove = $grid->addAction('remove');
        $remove->setTitle('Remove');
        $remove->onCanRender[] = function(DatabaseRow $row, Row $_row) use ($groupUsers) {
            return ($groupUsers > 1) && ($row->userId != $this->getUserId());
        };
        $remove->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
            $el = HTML::el('a') 
                ->text('Remove')
                ->class('grid-link')
                ->href($this->createURLString('removeUser', ['groupId' => $request->query['groupId'], 'userId' => $row->userId]));

            return $el;
        };

        return $grid;
    }

    public function handleAddUserForm(?FormResponse $fr = null) {
        $groupId = $this->httpGet('groupId', true);

        if($fr !== null) {
            try {
                $this->app->groupMembershipRepository->beginTransaction(__METHOD__);

                $this->app->groupManager->addUserToGroup($fr->user, $groupId);

                $this->app->groupMembershipRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User added to group.', 'success');
            } catch(AException $e) {
                $this->app->groupMembershipRepository->rollback(__METHOD__);

                $this->flashMessage('Could not add user to group. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('listUsers', ['groupId' => $groupId]));
        } else {
            try {
                $group = $this->app->groupManager->getGroupById($groupId);
            } catch(AException $e) {
                $this->flashMessage('This group does not exist.', 'error', 10);
                $this->redirect($this->createURL('list'));
            }
    
            $this->saveToPresenterCache('groupName', $group->title);
    
            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('listUsers', ['groupId' => $groupId]), 'link')
            ];
    
            $this->saveToPresenterCache('links', $links);
    
            $form = new FormBuilder();
    
            $form->setMethod()
                ->setAction($this->createURL('addUserForm', ['groupId' => $groupId]))
                ->addTextInput('username', 'Search user:', null, false)
                ->addButton('Search', 'searchUsers(\'' . $groupId . '\')', 'formSubmit')
                ->addSelect('user', 'User:', [], true)
                ->addSubmit('Add')
            ;
    
            $this->saveToPresenterCache('form', $form);
    
            $arb = new AjaxRequestBuilder();
            $arb->setAction($this, 'searchUsersForAddUserForm')
                ->setMethod()
                ->setHeader(['groupId' => '_groupId', 'query' => '_query'])
                ->setFunctionName('searchUsersAsync')
                ->setFunctionArguments(['_groupId', '_query'])
                ->addWhenDoneOperation('
                    if(obj.users.length == 0) {
                        alert("No users found.");
                    } else {
                        $("#user").html(obj.users);
                    }
                ')
            ;
    
            $this->addScript($arb);
            $this->addScript('
                async function searchUsers(groupId) {
                    const query = $("#username").val();

                    if(!query) {
                        alert("No username entered.");
                    } else {
                        await searchUsersAsync(groupId, query);
                    }
                }
            ');
        }
    }

    public function renderAddUserForm() {
        $this->template->group_title = $this->loadFromPresenterCache('groupName');
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->form = $this->loadFromPresenterCache('form');
    }

    public function actionSearchUsersForAddUserForm() {
        $groupId = $this->httpGet('groupId', true);
        $query = $this->httpGet('query', true);

        $groupUsers = $this->app->groupManager->getGroupUsersForGroupId($groupId);

        $qb = $this->app->userRepository->composeQueryForUsers();
        $qb->andWhere('username LIKE ?', ['%' . $query . '%'])
            ->andWhere($qb->getColumnNotInValues('userId', $groupUsers))
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = '<option value="' . $row['userId'] . '">' . $row['fullname'] . '</option>';
        }

        return ['users' => $users];
    }

    public function handleRemoveUser() {
        $groupId = $this->httpGet('groupId', true);
        $userId = $this->httpGet('userId', true);

        try {
            $this->app->groupMembershipRepository->beginTransaction(__METHOD__);
            
            $this->app->groupManager->removeUserFromGroup($userId, $groupId);

            $this->app->groupMembershipRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('User removed from group.');
        } catch(AException $e) {
            $this->app->groupMembershipRepository->rollback(__METHOD__);

            $this->flashMessage('Could not remove user from group. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('listUsers', ['groupId' => $groupId]));
    }
}

?>