<?php

namespace App\Components\ProcessReportsSidebar;

use App\Authorizators\SupervisorAuthorizator;
use App\Components\Sidebar\Sidebar2;
use App\Constants\Container\ProcessReportsViews;
use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;
use App\Managers\Container\StandaloneProcessManager;

class ProcessReportsSidebar extends Sidebar2 {
    private StandaloneProcessManager $standaloneProcessManager;
    private SupervisorAuthorizator $supervisorAuthorizator;

    public function __construct(HttpRequest $request, StandaloneProcessManager $standaloneProcessManager, SupervisorAuthorizator $supervisorAuthorizator) {
        parent::__construct($request);

        $this->standaloneProcessManager = $standaloneProcessManager;
        $this->supervisorAuthorizator = $supervisorAuthorizator;

        $this->setComponentName('processReportsSidebar');
    }

    public function startup() {
        parent::startup();

        $enabledProcessTypes = $this->standaloneProcessManager->getEnabledProcessTypes();

        $canSeeAll = $this->supervisorAuthorizator->canUserViewAllProcesses($this->app->currentUser->getId());

        foreach($enabledProcessTypes as $row) {
            $key = $row->typeKey;

            if(!StandaloneProcesses::areDefaultReportsEnabled($key)) {
                continue;
            }

            // my
            $myView = $key . '-' . ProcessReportsViews::VIEW_MY;
            $link = $this->createFullURL($this->httpRequest->get('page'), 'list', ['view' => $myView]);
            $this->addLink($row->title . ' (my)', $link, $this->checkIsViewActive($myView));

            // all
            if($canSeeAll) {
                $allView = $key . '-' . ProcessReportsViews::VIEW_ALL;
                $link = $this->createFullURL($this->httpRequest->get('page'), 'list', ['view' => $allView]);
                $this->addLink($row->title . ' (all)', $link, $this->checkIsViewActive($allView));
            }
        }

        $this->processPropertyMove();
    }

    /**
     * Checks if given view is active
     * 
     * @param string $view View name
     */
    private function checkIsViewActive(string $view): bool {
        if($this->httpRequest->get('view') !== null) {
            return $this->httpRequest->get('view') == $view;
        }

        return false;
    }

    /**
     * Processes property move
     */
    private function processPropertyMove() {
        $view = 'propertyItems-' . ProcessReportsViews::VIEW_MY;
        $url = $this->createFullURL($this->httpRequest->get('page'), 'list', ['view' => $view]);
        $this->addLink('Property items (my)', $url, $this->checkIsViewActive($view));

        if($this->supervisorAuthorizator->canUserViewAllProcesses($this->app->currentUser->getId())) {
            $view = 'propertyItems-' . ProcessReportsViews::VIEW_ALL;
            $url = $this->createFullURL($this->httpRequest->get('page'), 'list', ['view' => $view]);
            $this->addLink('Property items (all)', $url, $this->checkIsViewActive($view));
        }
    }
}

?>