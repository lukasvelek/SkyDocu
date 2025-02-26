<?php

namespace App\Modules\AdminModule;

class DbAdminPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('DbAdminPresenter', 'Database administration');

        $this->setSystem();
    }
}

?>