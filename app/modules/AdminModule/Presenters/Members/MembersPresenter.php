<?php

namespace App\Modules\AdminModule;

use App\Components\Widgets\GroupsWidget\GroupsWidget;
use App\Components\Widgets\UsersWidget\UsersWidget;

class MembersPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('MembersPresenter', 'Members');

        $this->setMembers();
    }

    public function renderDashboard() {}

    protected function createComponentUsersWidget() {
        /**
         * @var UsersWidget $widget
         */
        $widget = $this->componentFactory->createComponentInstanceByClassName(UsersWidget::class, [$this->groupRepository, $this->containerId]);

        return $widget;
    }

    protected function createComponentGroupsWidget() {
        /**
         * @var GroupsWidget $widget
         */
        $widget = $this->componentFactory->createComponentInstanceByClassName(GroupsWidget::class, [$this->groupRepository]);

        return $widget;
    }
}

?>