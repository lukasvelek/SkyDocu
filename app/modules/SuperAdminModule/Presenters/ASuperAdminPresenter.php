<?php

namespace App\Modules\SuperAdminModule;

use App\Core\Http\HttpRequest;
use App\Modules\APresenter;

abstract class ASuperAdminPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'SuperAdmin';
    }

    public function startup() {
        parent::startup();
    }

    private function checkAction(string ...$actions) {
        if(in_array($this->httpGet('action'), $actions)) {
            return true;
        } else {
            return false;
        }
    }

    protected function createComponentSidebar(HttpRequest $request) {
        $containerId = $request->query['containerId'];

        $sidebar = $this->componentFactory->getSidebar();

        $home = $this->checkAction('home');
        $status = $this->checkAction('status', 'listStatusHistory');
        $advanced = $this->checkAction('advanced');
        $usageStatistics = $this->checkAction('usageStatistics');
        $invites = $this->checkAction('invites', 'invitesWithoutGrid');

        $sidebar->addLink('&larr; Back', $this->createFullURL('SuperAdmin:Containers', 'list'));
        $sidebar->addLink('Home', $this->createURL('home', ['containerId' => $containerId]), $home);
        $sidebar->addLink('Status', $this->createURL('status', ['containerId' => $containerId]), $status);
        $sidebar->addLink('Usage statistics', $this->createURL('usageStatistics', ['containerId' => $containerId]), $usageStatistics);
        $sidebar->addLink('Invites', $this->createURL('invites', ['containerId' => $containerId]), $invites);
        $sidebar->addLink('Advanced', $this->createURL('advanced', ['containerId' => $containerId]), $advanced);

        return $sidebar;
    }
}

?>