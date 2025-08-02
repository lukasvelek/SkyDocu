<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\SystemGroups;
use App\Core\AjaxRequestBuilder;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\LinkHelper;
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

        $grid->addColumnConst('title', 'Title', SystemGroups::class);
        $col = $grid->addColumnText('containerId', 'Container');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');

            if($value === null) {
                return $value;
            } else {
                try {
                    $container = $this->app->containerManager->getContainerById($value);
    
                    $el->title($container->getTitle())
                        ->text($container->getTitle());
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
    
                    return $container->getTitle();
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

        return $grid;
    }

    public function renderListUsers() {
        $groupId = $this->httpRequest->get('groupId');
        if($groupId === null) {
            throw new RequiredAttributeIsNotSetException('groupId');
        }

        try {
            $group = $this->app->groupManager->getGroupById($groupId);
        } catch(AException $e) {
            $this->flashMessage('This group does not exist.', 'error', 10);
            $this->redirect($this->createURL('list'));
        }

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link'),
            LinkBuilder::createSimpleLink('Add user', $this->createURL('addUserForm', ['groupId' => $groupId]), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
        $this->template->group_name = SystemGroups::toString($group->title) ?? $group->title;
    }

    protected function createComponentGroupUsersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->groupMembershipRepository->composeQueryForGroupUsers($request->get('groupId')), 'groupUserId');

        $grid->addColumnUser('userId', 'User');
        $grid->addColumnDatetime('dateCreated', 'Member since');

        $groupUsers = $this->app->groupManager->getGroupUsersForGroupId($request->get('groupId'));
        $groupUserEntities = [];
        $technicalUsers = 0;
        foreach($groupUsers as $userId) {
            $user = $this->app->userManager->getUserById($userId);

            if($user->isTechnical()) {
                $technicalUsers++;
            }

            $groupUserEntities[$userId] = $user;
        }

        $remove = $grid->addAction('remove');
        $remove->setTitle('Remove');
        $remove->onCanRender[] = function(DatabaseRow $row, Row $_row) use ($groupUserEntities, $technicalUsers) {
            if(!$groupUserEntities[$row->userId]->isTechnical()) {
                return false;
            }
            if($groupUserEntities[$row->userId]->isTechnical() && $technicalUsers == 1) {
                return false;
            }

            return true;
        };
        $remove->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
            $el = HTML::el('a') 
                ->text('Remove')
                ->class('grid-link')
                ->href($this->createURLString('removeUser', ['groupId' => $request->get('groupId'), 'userId' => $row->userId]));

            return $el;
        };

        return $grid;
    }

    public function handleAddUserForm(?FormRequest $fr = null) {
        $groupId = $this->httpRequest->get('groupId');
        if($groupId === null) {
            throw new RequiredAttributeIsNotSetException('groupId');
        }

        if($fr !== null) {
            try {
                $group = $this->app->groupManager->getGroupById($groupId);

                $this->app->groupMembershipRepository->beginTransaction(__METHOD__);

                $this->app->groupManager->addUserToGroup($fr->user, $groupId);

                $this->app->groupMembershipRepository->commit($this->getUserId(), __METHOD__);

                if($group->containerId !== null) {
                    $this->app->containerManager->addUserToContainer($fr->user, $group->containerId);
                }

                $this->flashMessage('User added to group.', 'success');
            } catch(AException $e) {
                $this->app->groupMembershipRepository->rollback(__METHOD__);

                $this->flashMessage('Could not add user to group. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('listUsers', ['groupId' => $groupId]));
        }
    }

    public function renderAddUserForm() {
        $groupId = $this->httpRequest->get('groupId');

        try {
            $group = $this->app->groupManager->getGroupById($groupId);
        } catch(AException $e) {
            $this->flashMessage('This group does not exist.', 'error', 10);
            $this->redirect($this->createURL('list'));
        }

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
                    $("#formSubmit").removeAttr("disabled");
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

        $this->template->group_title = $group->title;
        $this->template->links = LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('listUsers', ['groupId' => $groupId]), 'link');
    }

    protected function createComponentAddUserForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('addUserForm', ['groupId' => $request->get('groupId')]));

        $form->addTextInput('email', 'Search user:');
        $form->addButton('Search')
            ->setOnClick('searchUsers(\'' . $request->get('groupId') . '\');');

        $form->addSelect('user', 'User:')
            ->setRequired();

        $form->addSubmit('Add')
            ->setDisabled();

        return $form;
    }

    public function actionSearchUsersForAddUserForm() {
        $groupId = $this->httpRequest->get('groupId');
        if($groupId === null) {
            throw new RequiredAttributeIsNotSetException('groupId');
        }
        $query = $this->httpRequest->get('query');
        if($query === null) {
            throw new RequiredAttributeIsNotSetException('query');
        }

        $groupUsers = $this->app->groupManager->getGroupUsersForGroupId($groupId);

        $usersDb = $this->app->userRepository->searchUsers($query, ['fullname', 'email'], $groupUsers);

        $users = [];
        foreach($usersDb as $user) {
            $users[] = '<option value="' . $user->getId() . '">' . $user->getFullname() . '</option>';
        }

        return new JsonResponse(['users' => $users]);
    }

    public function handleRemoveUser() {
        $groupId = $this->httpRequest->get('groupId', true);
        if($groupId === null) {
            throw new RequiredAttributeIsNotSetException('groupId');
        }
        $userId = $this->httpRequest->get('userId', true);
        if($userId === null) {
            throw new RequiredAttributeIsNotSetException('userId');
        }

        try {
            $this->app->groupMembershipRepository->beginTransaction(__METHOD__);
            
            $this->app->groupManager->removeUserFromGroup($userId, $groupId);

            $this->app->groupMembershipRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('User removed from group.', 'success');
        } catch(AException $e) {
            $this->app->groupMembershipRepository->rollback(__METHOD__);

            $this->flashMessage('Could not remove user from group. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('listUsers', ['groupId' => $groupId]));
    }
}

?>