<?php

namespace App\Modules\UserModule;

use App\Components\ProcessViewsSidebar\ProcessViewsSidebar;
use App\Core\Http\HttpRequest;

class NewProcessPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('NewProcessPresenter', 'New process');
    }

    public function renderSelect() {}

    protected function createComponentProcessViewsSidebar(HttpRequest $request) {
        $sidebar = new ProcessViewsSidebar($request);

        $sidebar->setNewProcessActive();

        return $sidebar;
    }
}

?>