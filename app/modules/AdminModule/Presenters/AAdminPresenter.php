<?php

namespace App\Modules\AdminModule;

use App\Modules\AContainerPresenter;

abstract class AAdminPresenter extends AContainerPresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'Admin';
    }
}

?>