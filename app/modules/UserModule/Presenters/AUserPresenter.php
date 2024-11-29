<?php

namespace App\Modules\UserModule;

use App\Modules\AContainerPresenter;

abstract class AUserPresenter extends AContainerPresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'User';
    }
}

?>