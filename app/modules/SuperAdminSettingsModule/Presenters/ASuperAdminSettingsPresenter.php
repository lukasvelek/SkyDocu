<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Modules\APresenter;

abstract class ASuperAdminSettingsPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'SuperAdminSettings';
    }
}

?>