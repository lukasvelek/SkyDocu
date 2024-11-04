<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class GroupsPresenter extends AAdminPresenter {
    private Sidebar $sidebar;

    public function __construct() {
        parent::__construct('GroupsPresenter', 'Groups');
    }

    public function startup() {
        parent::startup();

        $this->sidebar = new Sidebar();

        $this->sidebar->addLink('Dashboard', $this->createFullURL('Admin:Members', 'dashboard'), false);
        $this->sidebar->addLink('Groups', $this->createFullURL('Admin:Groups', 'list'), true);
        $this->sidebar->addLink('Users', $this->createFullURL('Admin:Users', 'list'), false);
    }

    public function renderList() {
        $this->template->sidebar = $this->sidebar;
        $this->template->links = [
            LinkBuilder::createSimpleLink('New group', $this->createURL('newForm'), 'link')
        ];
    }

    protected function createComponentGroupsGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

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
        $this->template->sidebar = $this->sidebar;
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentGroupMembersGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

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
        $groupId = $this->httpGet('groupId', true);

        if($fr !== null) {

        } else {
            $container = $this->app->containerManager->getContainerById($this->httpSessionGet('container'));

            $containerUsers = $this->app->groupManager->getGroupUsersForGroupId($container->title . ' - users');
            $groupUsers = $this->groupRepository->getMembersForGroup($groupId);

            $users = [];
            foreach($containerUsers as $user) {
                if(!in_array($user, $groupUsers)) {
                    $users[] = [
                        'value' => $user,
                        'text' => $this->app->userManager->getUserById($user)->getFullname()
                    ];
                }
            }

            $form = new FormBuilder();

            $form->setMethod()
                ->setAction($this->createURL('addMemberForm', ['groupId' => $groupId]))
                ->addSelect('user', 'User:', $users, true)
                ->addSubmit('Add')
            ;

            $this->saveToPresenterCache('form', $form);
        }
    }

    public function renderAddMemberForm() {
        $groupId = $this->httpGet('groupId');

        $this->template->form = $this->loadFromPresenterCache('form');
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('listMembers', ['groupId' => $groupId]), 'link')
        ];
    }
}

?>