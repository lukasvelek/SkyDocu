<?php

namespace App\Modules\AdminModule;

use App\Components\Widgets\GroupsWidget\GroupsWidget;
use App\Components\Widgets\UsersWidget\UsersWidget;
use App\Core\Http\HttpRequest;

class MembersPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('MembersPresenter', 'Members');

        $this->setMembers();
    }

    public function renderDashboard() {}

    protected function createComponentUsersWidget(HttpRequest $request) {
        $widget = new UsersWidget($request, $this->app->userRepository);

        return $widget;
    }

    protected function createComponentGroupsWidget(HttpRequest $request) {
        $widget = new GroupsWidget($request, $this->groupRepository);

        return $widget;
    }
}

?>