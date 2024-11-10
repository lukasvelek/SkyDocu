<?php

namespace App\Modules\SuperAdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\APresenter;

abstract class ASuperAdminPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'SuperAdmin';
    }

    public function startup() {
        parent::startup();

        if($this->name == 'ContainerSettingsPresenter') {
            $this->addBeforeRenderCallback(function() {
                $this->template->sidebar = $this->createContainerSettingsSidebar();
            });
        }
    }

    private function createContainerSettingsSidebar() {
        $containerId = $this->httpGet('containerId', true);

        $sb = new Sidebar();

        $home = $this->checkAction('home');
        $status = $this->checkAction('status', 'listStatusHistory');
        $advanced = $this->checkAction('advanced');

        $sb->addLink('Home', $this->createURL('home', ['containerId' => $containerId]), $home);
        $sb->addLink('Status', $this->createURL('status', ['containerId' => $containerId]), $status);
        $sb->addLink('Advanced', $this->createURL('advanced', ['containerId' => $containerId], $advanced));

        return $sb->render();
    }

    private function checkPage(string $page) {
        return $this->httpGet('page') == $page;
    }

    private function checkAction(string ...$actions) {
        if(in_array($this->httpGet('action'), $actions)) {
            return true;
        } else {
            return false;
        }
    }
}

?>