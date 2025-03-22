<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\SystemGroups;
use App\Constants\SessionNames;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class GroupsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('GroupsPresenter', 'Groups');

        $this->setMembers();
    }

    public function renderList() {
        $this->template->links = LinkBuilder::createSimpleLink('New group', $this->createURL('newForm'), 'link');
    }

    protected function createComponentGroupsGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $grid->createDataSourceFromQueryBuilder($this->groupRepository->composeQueryForGroups(), 'groupId');

        $col = $grid->addColumnText('title', 'Title');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            if(array_key_exists($value, SystemGroups::getAll())) {
                return SystemGroups::toString($value);
            } else {
                return $value;
            }
        };
        $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
            if(array_key_exists($value, SystemGroups::getAll())) {
                return SystemGroups::toString($value);
            } else {
                return $value;
            }
        };

        $members = $grid->addAction('members');
        $members->onCanRender[] = function() {
            return true;
        };
        $members->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->title('Members')
                ->text('Members')
                ->href($this->createURLString('listMembers', ['groupId' => $primaryKey]))
                ->class('grid-link')
            ;

            return $el;
        };

        $grid->addQuickSearch('title', 'Title');

        return $grid;
    }

    public function renderListMembers() {
        $groupId = $this->httpRequest->get('groupId');
        if($groupId === null) {
            throw new RequiredAttributeIsNotSetException('groupId');
        }
        $group = $this->groupRepository->getGroupById($groupId);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link'),
        ];

        if($group['title'] != 'All users') {
            $links[] = LinkBuilder::createSimpleLink('Add member', $this->createURL('addMemberForm', ['groupId' => $groupId]), 'link');
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentGroupMembersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $group = $this->groupRepository->getGroupById($request->get('groupId'));

        $grid->createDataSourceFromQueryBuilder($this->groupRepository->composeQueryForGroupMembers($request->get('groupId')), 'relationId');
        $grid->addQueryDependency('groupId', $request->get('groupId'));

        $grid->addColumnUser('userId', 'User');

        if($group['title'] != SystemGroups::ALL_USERS) {
            $remove = $grid->addAction('remove');
            $remove->onCanRender[] = function(DatabaseRow $row, Row $_row) use ($group) {
                if($this->app->groupManager->isUserMemberOfSuperadministrators($row->userId) && $group['title'] == SystemGroups::ADMINISTRATORS) {
                    return false;
                }

                return true;
            };
            $remove->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
                $el = HTML::el('a')
                    ->title('Remove')
                    ->text('Remove')
                    ->href($this->createURLString('removeGroupMember', ['groupId' => $request->get('groupId'), 'userId' => $row->userId]))
                    ->class('grid-link')
                ;

                return $el;
            };
        }

        return $grid;
    }

    public function handleAddMemberForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $groupId = $this->httpRequest->get('groupId');
            if($groupId === null) {
                throw new RequiredAttributeIsNotSetException('groupId');
            }

            try {
                $this->groupRepository->beginTransaction(__METHOD__);

                $this->groupManager->addUserToGroupId($groupId, $fr->user);

                $this->groupRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User added to group.', 'success');
            } catch(AException $e) {
                $this->groupRepository->rollback(__METHOD__);

                $this->flashMessage('Could not add user to group. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('listMembers', ['groupId' => $groupId]));
        }
    }

    public function renderAddMemberForm() {
        $this->template->links = $this->createBackUrl('listMembers', ['groupId' => $this->httpRequest->get('groupId')]);
    }

    protected function createComponentNewMemberForm(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($this->httpSessionGet(SessionNames::CONTAINER));

        $containerUsers = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');
        $groupUsers = $this->groupRepository->getMembersForGroup($request->get('groupId'));

        $users = [];
        foreach($containerUsers as $user) {
            if(!in_array($user, $groupUsers)) {
                $users[] = [
                    'value' => $user,
                    'text' => $this->app->userManager->getUserById($user)->getFullname()
                ];
            }
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('addMemberForm', ['groupId' => $request->get('groupId')]));

        $select = $form->addSelect('user', 'User:')
            ->setRequired()
            ->addRawOptions($users);

        $submit = $form->addSubmit('Add');

        if(empty($users)) {
            $select->setDisabled();
            $select->addRawOption('none', 'No users available.', true);

            $submit->setDisabled();
        }

        return $form;
    }

    public function handleRemoveGroupMember() {
        $groupId = $this->httpRequest->get('groupId');
        if($groupId === null) {
            throw new RequiredAttributeIsNotSetException('groupId');
        }
        $userId = $this->httpRequest->get('userId');
        if($userId === null) {
            throw new RequiredAttributeIsNotSetException('userId');
        }

        try {
            $this->groupRepository->beginTransaction(__METHOD__);

            $this->groupManager->removeUserFromGroupId($groupId, $userId);

            $this->groupRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('User removed from group.', 'success');
        } catch(AException $e) {
            $this->groupRepository->rollback(__METHOD__);

            $this->flashMessage('Could not remove user from group. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('listMembers', ['groupId' => $groupId]));
    }

    public function handleNewForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->groupRepository->beginTransaction(__METHOD__);

                $this->groupManager->createNewGroup($fr->title);

                $this->groupRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Group created successfully.', 'success');
            } catch(AException $e) {
                $this->groupRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create new group. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentNewGroupForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newForm'));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }
}

?>