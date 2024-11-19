<?php

namespace App\Modules\AdminModule;

use App\Core\Http\HttpRequest;
use App\Modules\AContainerPresenter;

abstract class AAdminPresenter extends AContainerPresenter {
    private bool $isMembers;
    private bool $isDocuments;
    private bool $isSystem;

    private array $sidebarLinks;

    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'Admin';
        $this->isMembers = false;
        $this->isDocuments = false;
        $this->isSystem = false;

        $this->sidebarLinks = [];
    }

    protected function setDocuments() {
        $this->isDocuments = true;
    }

    protected function setMembers() {
        $this->isMembers = true;
    }

    protected function setSystem() {
        $this->isSystem = true;
    }

    public function startup() {
        parent::startup();

        $addLink = function(string $title, array $url, bool $isActive) {
            $this->sidebarLinks[] = [
                'title' => $title,
                'url' => $url,
                'isActive' => $isActive
            ];
        };

        if($this->isMembers) {
            $members = $this->checkActivePage('Members');
            $groups = $this->checkActivePage('Groups');
            $users = $this->checkActivePage('Users');

            $addLink('Dashboard', $this->createFullURL('Admin:Members', 'dashboard'), $members);
            $addLink('Groups', $this->createFullURL('Admin:Groups', 'list'), $groups);
            $addLink('Users', $this->createFullURL('Admin:Users', 'list'), $users);
        }

        if($this->isDocuments) {
            $documents = $this->checkActivePage('Documents');
            $folders = $this->checkActivePage('DocumentFolders');
            $metadata = $this->checkActivePage('DocumentMetadata');

            $addLink('Dashboard', $this->createFullURL('Admin:Documents', 'dashboard'), $documents);
            $addLink('Folders', $this->createFullURL('Admin:DocumentFolders', 'list'), $folders);
            $addLink('Metadata', $this->createFullURL('Admin:DocumentMetadata', 'list'), $metadata);
        }

        if($this->isSystem) {
            $system = $this->checkActivePage('System');
            $transactionLog = $this->checkActivePage('TransactionLog');

            $addLink('Dashboard', $this->createFullURL('Admin:System', 'dashboard'), $system);
            $addLink('Transaction log', $this->createFullURL('Admin:TransactionLog', 'list'), $transactionLog);
        }
    }

    protected function checkActivePage(string $key) {
        $name = substr($this->name, 0, -9); //Presenter

        return $name == $key;
    }

    protected function createComponentSidebar(HttpRequest $request) {
        $sidebar = $this->componentFactory->getSidebar();

        foreach($this->sidebarLinks as $data) {
            $title = $data['title'];
            $url = $data['url'];
            $isActive = $data['isActive'];

            $sidebar->addLink($title, $url, $isActive);
        }

        return $sidebar;
    }
}

?>