<?php

namespace App\Components\ProcessReportsSidebar;

use App\Authorizators\SupervisorAuthorizator;
use App\Components\Sidebar\Sidebar2;
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

            // my
            $myView = $key . '-my';
            $link = $this->createFullURL($this->httpRequest->get('page'), 'list', ['view' => $myView]);
            $this->addLink($row->title . ' (my)', $link, $this->checkIsViewActive($myView));

            // all
            if($canSeeAll) {
                $allView = $key . '-all';
                $link = $this->createFullURL($this->httpRequest->get('page'), 'list', ['view' => $allView]);
                $this->addLink($row->title . ' (all)', $link, $this->checkIsViewActive($allView));
            }
        }
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
}

?>