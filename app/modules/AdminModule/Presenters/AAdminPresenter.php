<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\AContainerPresenter;
use App\Modules\TemplateObject;

abstract class AAdminPresenter extends AContainerPresenter {
    private bool $isMembers;
    private Sidebar $sidebar;

    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'Admin';
        $this->isMembers = false;
    }

    protected function setMembers() {
        $this->isMembers = true;
    }

    public function startup() {
        parent::startup();

        if($this->isMembers) {
            $members = $this->checkActivePage('Members');
            $groups = $this->checkActivePage('Groups');
            $users = $this->checkActivePage('Users');

            $this->sidebar = new Sidebar();

            $this->sidebar->addLink('Dashboard', $this->createFullURL('Admin:Members', 'dashboard'), $members);
            $this->sidebar->addLink('Groups', $this->createFullURL('Admin:Groups', 'list'), $groups);
            $this->sidebar->addLink('Users', $this->createFullURL('Admin:Users', 'list'), $users);

            $this->addBeforeRenderCallback(function(TemplateObject $template) {
                $template->sidebar = $this->sidebar;
            });
        }
    }

    protected function checkActivePage(string $key) {
        $name = substr($this->name, 0, -9); //Presenter

        return $name == $key;
    }
}

?>