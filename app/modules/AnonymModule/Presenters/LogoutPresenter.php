<?php

namespace App\Modules\AnonymModule;

class LogoutPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('LogoutPresenter', 'Logout');
    }

    public function handleLogout() {
        session_destroy();

        $this->redirect($this->createFullURL('Anonym:AutoLogin', 'checkLogin'));
    }
}

?>