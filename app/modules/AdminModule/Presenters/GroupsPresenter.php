<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
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
            LinkBuilder::createSimpleLink('New groups', $this->createURL('newForm'), 'link')
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

    public function renderListMembers() {
        $groupId = $this->httpGet('groupId');

        $this->template->sidebar = $this->sidebar;
        $this->template->links = [
            LinkBuilder::createSimpleLink('Add member', $this->createURL('addMemberForm', ['groupId' => $groupId]), 'link')
        ];
    }

    protected function createComponentGroupMembersGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->groupRepository->composeQueryForGroupMembers($request->query['groupId']), 'relationId');
        $grid->addQueryDependency('groupId', $request->query['groupId']);

        $grid->addColumnUser('userId', 'User');

        $remove = $grid->addAction('remove');
        $remove->onCanRender[] = function(DatabaseRow $row, Row $_row) {
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

        return $grid;
    }
}

?>