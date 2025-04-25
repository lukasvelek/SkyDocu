<?php

namespace App\Components\ProcessViewsSidebar;

use App\Components\Sidebar\Sidebar2;
use App\Constants\Container\ProcessGridViews;
use App\Core\Http\HttpRequest;

/**
 * This sidebar contains links to all process grid views as well as other necessary links
 * 
 * @author Lukas Velek
 */
class ProcessViewsSidebar extends Sidebar2 {
    private bool $isNewActive = false;

    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->setComponentName('processViewsSidebar');
    }

    public function setNewProcessActive(bool $isNewActive = true) {
        $this->isNewActive = $isNewActive;
    }

    public function startup() {
        parent::startup();

        $this->addLink('Start new process', $this->createFullURL('User:NewProcess', 'select'), $this->isNewActive);
        $this->addHorizontalLine();

        $url = [
            'page' => 'User:Processes',
            'action' => 'list'
        ];

        foreach(ProcessGridViews::getAll() as $key => $title) {
            $url['view'] = $key;

            $isActive = false;
            if($this->httpRequest->get('view') == $key) {
                $isActive = true;
            }

            $this->addLink($title, $url, $isActive);
        }
    }
}

?>