<?php

namespace App\Components\ProcessViewSidebar;

use App\Authorizators\SupervisorAuthorizator;
use App\Components\Sidebar\Sidebar2;
use App\Constants\Container\ProcessGridViews;
use App\Core\Http\HttpRequest;

class ProcessViewSidebar extends Sidebar2 {
    private SupervisorAuthorizator $supervisorAuthorizator;
    private string $currentUserId;

    public function __construct(HttpRequest $request, SupervisorAuthorizator $supervisorAuthorizator, string $currentUserId) {
        parent::__construct($request);

        $this->supervisorAuthorizator = $supervisorAuthorizator;
        $this->currentUserId = $currentUserId;

        $this->setComponentName('processViewSidebar');
    }

    public function startup() {
        parent::startup();

        $links = ProcessGridViews::getAll();

        if(!$this->supervisorAuthorizator->canUserViewAllProcesses($this->currentUserId)) {
            unset($links[ProcessGridViews::VIEW_ALL]);
        }

        foreach($links as $name => $title) {
            $this->addLink($title, $this->createFullURL('User:Processes', 'list', ['view' => $name]), $this->checkIsViewActive($name));
        }

        /** CUSTOM STATIC LINKS */

        $this->addStaticLink('Start new process', $this->createFullURL('User:NewProcess', 'select'), $this->checkIsLinkActive(['page' => 'User:NewProcess', 'action' => 'select']));

        /** END OF CUSTOM STATIC LINKS */
    }

    public function prerender() {
        parent::prerender();

        if(!empty($this->staticLinks)) {
            array_unshift($this->links, '<hr>');

            foreach($this->staticLinks as $link) {
                array_unshift($this->links, $link);
            }
        }
    }

    /**
     * Checks if given view is active
     * 
     * @param string $view View name
     * @return bool True if active or false if not
     */
    private function checkIsViewActive(string $view) {
        if($this->httpRequest->get('view') !== null) {
            return $this->httpRequest->get('view') == $view;
        }

        return false;
    }
}

?>