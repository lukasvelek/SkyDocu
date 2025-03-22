<?php

namespace App\Modules\AnonymModule;

use App\Constants\SessionNames;

class AutoLoginPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('AutoLoginPresenter', 'Autologin');
    }

    public function handleCheckLogin() {
        if($this->httpSessionGet(SessionNames::USER_ID) === null) {
            $url = ['page' => 'Anonym:Login', 'action' => 'loginForm'];
            $this->httpSessionSet(SessionNames::IS_LOGGING_IN, 'true');
        } else {
            $url = $this->calculateUserNextDestination();
        }

        $this->redirect($url);
    }

    private function calculateUserNextDestination() {
        if($this->httpSessionGet(SessionNames::IS_CHOOSING_CONTAINER) !== null) {
            // redirect to container choose form
            return ['page' => 'Anonym:Login', 'action' => 'containerForm'];
        }

        if($this->getUserId() !== null && ($this->app->groupManager->isUserMemberOfSuperadministrators($this->getUserId()) || ($this->app->groupManager->isUserMemberOfSuperadministrators($this->getUserId()) && ($this->httpSessionGet(SessionNames::CONTAINER) !== null && $this->httpSessionGet(SessionNames::CONTAINER) == 'superadministrators')))) {
            // redirect to superadmin
            return ['page' => 'SuperAdmin:Home', 'action' => 'home'];
        } else if($this->httpSessionGet(SessionNames::CONTAINER) !== null) {
            // redirect to their container
            return ['page' => 'User:Home', 'action' => 'dashboard'];
        } else {
            // redirect to login form
            $this->httpSessionSet(SessionNames::IS_LOGGING_IN, 'true');
            return ['page' => 'Anonym:Login', 'action' => 'loginForm'];
        }
    }
}

?>