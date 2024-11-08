<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\TemplateObject;

class DocumentsPresenter extends AAdminPresenter {
    private Sidebar $sidebar;

    public function __construct() {
        parent::__construct('DocumentsPresenter', 'Documents');
    }

    public function startup() {
        parent::startup();

        $this->sidebar = new Sidebar();

        $this->sidebar->addLink('Dashboard', $this->createFullURL('Admin:Documents', 'dashboard'), true);
        $this->sidebar->addLink('Folders', $this->createFullURL('Admin:DocumentFolders', 'list'), false);
        $this->sidebar->addLink('Metadata', $this->createFullURL('Admin:DocumentMetadata', 'list'), false);

        $this->addBeforeRenderCallback(function(TemplateObject $template) {
            $template->sidebar = $this->sidebar;
        });
    }

    public function renderDashboard() {}
}

?>