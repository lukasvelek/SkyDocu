<?php

namespace App\Modules\AdminModule;

use App\Core\Http\HttpRequest;
use App\Modules\AContainerPresenter;

abstract class AAdminPresenter extends AContainerPresenter {
    private bool $isMembers;
    private bool $isDocuments;
    private bool $isSystem;
    private bool $isProcesses;
    private bool $isArchive;

    private array $sidebarLinks;

    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'Admin';
        $this->isMembers = false;
        $this->isDocuments = false;
        $this->isSystem = false;
        $this->isProcesses = false;
        $this->isArchive = false;

        $this->sidebarLinks = [];
    }

    protected function setDocuments() {
        $this->isDocuments = true;
    }

    protected function setArchive() {
        $this->isArchive = true;
    }

    protected function setMembers() {
        $this->isMembers = true;
    }

    protected function setSystem() {
        $this->isSystem = true;
    }

    protected function setProcesses() {
        $this->isProcesses = true;
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
        
        if($this->isArchive) {
            $archive = $this->checkActivePage('Archive');
            $folders = $this->checkActivePage('ArchiveFolders');
            
            $addLink('Archive', $this->createFullURL('Admin:Archive', 'dashboard'), $archive);
            $addLink('Folders', $this->createFullURL('Admin:ArchiveFolders', 'list'), $folders);
        }

        if($this->isSystem) {
            $system = $this->checkActivePage('System');
            $transactionLog = $this->checkActivePage('TransactionLog');
            $gridConfiguration = $this->checkActivePage('GridConfiguration');
            $fileStorage = $this->checkActivePage('FileStorage');
            $dbAdmin = $this->checkActivePage('DbAdmin');
            $externalSystems = $this->checkActivePage('ExternalSystems');

            $addLink('Dashboard', $this->createFullURL('Admin:System', 'dashboard'), $system);
            $addLink('Transaction log', $this->createFullURL('Admin:TransactionLog', 'list'), $transactionLog);
            $addLink('Grid configuration', $this->createFullURL('Admin:GridConfiguration', 'list'), $gridConfiguration);
            $addLink('File storage', $this->createFullURL('Admin:FileStorage', 'list'), $fileStorage);
            $addLink('Database administration', $this->createFullURL('Admin:DbAdmin', 'list'), $dbAdmin);
            $addLink('External systems', $this->createFullURL('Admin:ExternalSystems', 'list'), $externalSystems);
        }

        if($this->isProcesses) {
            $processes = $this->checkActivePage('Processes', 'dashboard');
            $processList = $this->checkActivePage('Processes', 'list') || $this->checkActivePage('ProcessMetadata');
            $reportList = $this->checkActivePage('ProcessReports', 'list');

            $addLink('Dashboard', $this->createFullURL('Admin:Processes', 'dashboard'), $processes);
            $addLink('Process list', $this->createFullURL('Admin:Processes', 'list'), $processList);
            $addLink('Process reports', $this->createFullURL('Admin:ProcessReports', 'list'), $reportList);
        }
    }

    /**
     * Checks active page
     * 
     * @param string $key Presenter name (without Presenter suffix)
     * @param ?string $action Optional action name for additional checking
     */
    protected function checkActivePage(string $key, ?string $action = null): bool {
        $name = substr($this->name, 0, -9); //Presenter

        if($action === null) {
            return $name == $key;
        } else {
            if($name == $key) {
                if($this->httpRequest->get('action') == $action) {
                    return true;
                }
            }

            return false;
        }
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