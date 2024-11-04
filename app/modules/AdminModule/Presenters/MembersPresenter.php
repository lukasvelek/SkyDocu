<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;

class MembersPresenter extends AAdminPresenter {
    private Sidebar $sidebar;

    public function __construct() {
        parent::__construct('MembersPresenter', 'Members');
    }

    public function startup() {
        parent::startup();

        $this->sidebar = new Sidebar();

        $this->sidebar->addLink('Dashboard', $this->createFullURL('Admin:Members', 'dashboard'), true);
        $this->sidebar->addLink('Groups', $this->createFullURL('Admin:Groups', 'list'), false);
        $this->sidebar->addLink('Users', $this->createFullURL('Admin:Users', 'list'), false);
    }

    public function handleDashboard() {}

    public function renderDashboard() {
        $this->template->sidebar = $this->sidebar;
    }
}

?>