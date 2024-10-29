<?php

namespace App\Modules\SuperAdminModule;

use App\Modules\APresenter;

abstract class ASuperAdminPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'SuperAdmin';
    }

    public function startup() {
        parent::startup();
    }
}

?>