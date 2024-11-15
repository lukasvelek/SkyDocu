<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\AContainerPresenter;
use App\Modules\TemplateObject;

abstract class AAdminPresenter extends AContainerPresenter {
    private bool $isMembers;
    private bool $isDocuments;
    private bool $isSystem;

    private bool $isSidebar;

    private Sidebar $sidebar;

    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'Admin';
        $this->isMembers = false;
        $this->isDocuments = false;
        $this->isSystem = false;

        $this->isSidebar = false;
    }

    protected function setDocuments() {
        $this->isDocuments = true;
        $this->isSidebar = true;
    }

    protected function setMembers() {
        $this->isMembers = true;
        $this->isSidebar = true;
    }

    protected function setSystem() {
        $this->isSystem = true;
        $this->isSidebar = true;
    }

    public function startup() {
        parent::startup();

        if($this->isSidebar) {
            $this->sidebar = new Sidebar();
        }

        if($this->isMembers) {
            $members = $this->checkActivePage('Members');
            $groups = $this->checkActivePage('Groups');
            $users = $this->checkActivePage('Users');

            $this->sidebar->addLink('Dashboard', $this->createFullURL('Admin:Members', 'dashboard'), $members);
            $this->sidebar->addLink('Groups', $this->createFullURL('Admin:Groups', 'list'), $groups);
            $this->sidebar->addLink('Users', $this->createFullURL('Admin:Users', 'list'), $users);
        }

        if($this->isDocuments) {
            $documents = $this->checkActivePage('Documents');
            $folders = $this->checkActivePage('DocumentFolders');
            $metadata = $this->checkActivePage('DocumentMetadata');

            $this->sidebar->addLink('Dashboard', $this->createFullURL('Admin:Documents', 'dashboard'), $documents);
            $this->sidebar->addLink('Folders', $this->createFullURL('Admin:DocumentFolders', 'list'), $folders);
            $this->sidebar->addLink('Metadata', $this->createFullURL('Admin:DocumentMetadata', 'list'), $metadata);
        }

        if($this->isSystem) {
            $system = $this->checkActivePage('System');
            $transactionLog = $this->checkActivePage('TransactionLog');

            $this->sidebar->addLink('Dashboard', $this->createFullURL('Admin:System', 'dashboard'), $system);
            $this->sidebar->addLink('Transaction log', $this->createFullURL('Admin:TransactionLog', 'list'), $transactionLog);
        }

        if($this->isSidebar) {
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