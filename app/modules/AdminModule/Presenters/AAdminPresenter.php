<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\AContainerPresenter;
use App\Modules\TemplateObject;

abstract class AAdminPresenter extends AContainerPresenter {
    private bool $isMembers;
    private bool $isDocuments;

    private Sidebar $sidebar;

    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'Admin';
        $this->isMembers = false;
        $this->isDocuments = false;
    }

    protected function setDocuments() {
        $this->isDocuments = true;
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

        if($this->isDocuments) {
            $documents = $this->checkActivePage('Documents');
            $folders = $this->checkActivePage('DocumentFolders');
            $metadata = $this->checkActivePage('DocumentMetadata');

            $this->sidebar = new Sidebar();

            $this->sidebar->addLink('Dashboard', $this->createFullURL('Admin:Documents', 'dashboard'), $documents);
            $this->sidebar->addLink('Folders', $this->createFullURL('Admin:DocumentFolders', 'list'), $folders);
            $this->sidebar->addLink('Metadata', $this->createFullURL('Admin:DocumentMetadata', 'list'), $metadata);

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