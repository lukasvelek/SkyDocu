<?php

namespace App\Modules\AdminModule;

use App\Modules\AContainerPresenter;

abstract class AAdminPresenter extends AContainerPresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'Admin';
    }

    protected function checkActivePage(string $key) {
        $name = substr($this->name, 0, -9); //Presenter

        return $name == $key;
    }
}

?>