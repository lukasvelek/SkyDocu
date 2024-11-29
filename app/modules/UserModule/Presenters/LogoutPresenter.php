<?php

namespace App\Modules\UserModule;

class LogoutPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('LogoutPresenter', 'Logout');
    }

    public function handleLogout() {
        session_destroy();

        $this->redirect($this->createFullURL('Anonym:AutoLogin', 'checkLogin'));
    }
}

?>