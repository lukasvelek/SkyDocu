<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class GroupsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('GroupsPresenter', 'Groups');

        $this->setMembers();
    }

    public function renderList() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('New group', $this->createURL('newForm'), 'link')
        ];
    }

    protected function createComponentGroupsGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->groupRepository->composeQueryForGroups(), 'groupId');

        $grid->addColumnText('title', 'Title');

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

        return $grid;
    }

    public function handleListMembers() {
        $groupId = $this->httpGet('groupId');
        $group = $this->groupRepository->getGroupById($groupId);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link'),
        ];

        if($group['title'] != 'All users') {
            $links[] = LinkBuilder::createSimpleLink('Add member', $this->createURL('addMemberForm', ['groupId' => $groupId]), 'link');
        }
        
        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderListMembers() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentGroupMembersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $group = $this->groupRepository->getGroupById($request->query['groupId']);

        $grid->createDataSourceFromQueryBuilder($this->groupRepository->composeQueryForGroupMembers($request->query['groupId']), 'relationId');
        $grid->addQueryDependency('groupId', $request->query['groupId']);

        $grid->addColumnUser('userId', 'User');

        if($group['title'] != 'All users') {
            $remove = $grid->addAction('remove');
            $remove->onCanRender[] = function(DatabaseRow $row, Row $_row) use ($group) {
                if($this->app->groupManager->isUserMemberOfSuperadministrators($row->userId)) {
                    return false;
                }

                return true;
            };
            $remove->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
                $el = HTML::el('a')
                    ->title('Remove')
                    ->text('Remove')
                    ->href($this->createURLString('removeGroupMember', ['groupId' => $request->query['groupId'], 'userId' => $primaryKey]))
                    ->class('grid-link')
                ;

                return $el;
            };
        }

        return $grid;
    }

    public function handleAddMemberForm(?FormResponse $fr = null) {
        if($fr !== null) {
            $groupId = $this->httpGet('groupId', true);

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
        $this->template->links = $this->createBackUrl('listMembers', ['groupId', $this->httpGet('groupId')]);
    }

    protected function createComponentNewMemberForm(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($this->httpSessionGet('container'));

        $containerUsers = $this->app->groupManager->getGroupUsersForGroupId($container->title . ' - users');
        $groupUsers = $this->groupRepository->getMembersForGroup($request->query['groupId']);

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

        $form->setAction($this->createURL('addMemberForm', ['groupId' => $request->query['groupId']]));

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
}

?>