<?php

namespace App\Modules\UserModule;

use App\Components\ProcessSelect\ProcessSelect;
use App\Components\ProcessViewsSidebar\ProcessViewsSidebar;
use App\Core\Http\HttpRequest;

class NewProcessPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('NewProcessPresenter', 'New process');
    }

    public function renderSelect() {}

    protected function createComponentProcessViewsSidebar(HttpRequest $request) {
        /**
         * @var ProcessViewsSidebar $sidebar
         */
        $sidebar = $this->componentFactory->createComponentInstanceByClassName(ProcessViewsSidebar::class);

        $sidebar->setNewProcessActive();

        return $sidebar;
    }

    protected function createComponentProcessSelect(HttpRequest $request) {
        $processSelect = $this->componentFactory->createComponentInstanceByClassName(ProcessSelect::class, [
            $this->processManager,
            $this->processRepository
        ]);

        return $processSelect;
    }
}

?>