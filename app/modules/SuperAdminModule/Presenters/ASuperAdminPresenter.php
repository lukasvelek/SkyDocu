<?php

namespace App\Modules\SuperAdminModule;

use App\Core\Http\HttpRequest;
use App\Modules\APresenter;

abstract class ASuperAdminPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'SuperAdmin';
    }

    private function checkAction(string ...$actions) {
        if(in_array($this->httpRequest->get('action'), $actions)) {
            return true;
        } else {
            return false;
        }
    }

    protected function createComponentSidebar(HttpRequest $request) {
        $containerId = $request->get('containerId');

        $sidebar = $this->componentFactory->getSidebar();

        $home = $this->checkAction('home');
        $status = $this->checkAction('status', 'listStatusHistory');
        $advanced = $this->checkAction('advanced');
        $usageStatistics = $this->checkAction('usageStatistics', 'clearUsageStatistics');
        $invites = $this->checkAction('invites', 'invitesWithoutGrid');
        $transactionLog = $this->checkAction('transactionLog');

        $sidebar->addLink('&larr; Back', $this->createFullURL('SuperAdmin:Containers', 'list'));
        $sidebar->addLink('Home', $this->createURL('home', ['containerId' => $containerId]), $home);
        $sidebar->addLink('Status', $this->createURL('status', ['containerId' => $containerId]), $status);
        $sidebar->addLink('Statistics', $this->createURL('usageStatistics', ['containerId' => $containerId]), $usageStatistics);
        $sidebar->addLink('Invites', $this->createURL('invites', ['containerId' => $containerId]), $invites);
        $sidebar->addLink('Advanced', $this->createURL('advanced', ['containerId' => $containerId]), $advanced);
        $sidebar->addLink('Transaction log', $this->createURL('transactionLog', ['containerId' => $containerId]), $transactionLog);

        return $sidebar;
    }
}

?>