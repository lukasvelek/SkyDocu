<?php

namespace App\Modules\SuperAdminModule;

class LogoutPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('LogoutPresenter', 'Logout');
    }

    public function handleLogout() {
        session_destroy();

        $this->redirect($this->createFullURL('Anonym:AutoLogin', 'checkLogin'));
    }
}

?>