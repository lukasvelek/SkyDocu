<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\TemplateObject;

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

        $this->addBeforeRenderCallback(function(TemplateObject $template) {
            $template->sidebar = $this->sidebar;
        });
    }

    public function renderDashboard() {}
}

?>