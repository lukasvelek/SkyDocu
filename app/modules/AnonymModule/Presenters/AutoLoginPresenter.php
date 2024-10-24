<?php

namespace App\Modules\AnonymModule;

class AutoLoginPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('AutoLoginPresenter', 'Autologin');
    }

    public function handleCheckLogin() {
        $fm = $this->httpGet('_fm');

        if($this->httpSessionGet('userId') === null) {
            $url = ['page' => 'Anonym:Login', 'action' => 'loginForm'];
            $this->httpSessionSet('is_logging_in', 'true');
        } else {
            $url = $this->calculateUserNextDestination();
        }

        if($fm !== null) {
            $url['_fm'] = $fm;
        }

        $this->redirect($url);
    }

    private function calculateUserNextDestination() {
        if($this->app->groupManager->isUserMemberOfSuperadministrators($this->getUserId())) {
            // redirect to superadmin
            return ['page' => 'SuperAdmin:Home', 'action' => 'home'];
        } else {
            // redirect to their container
        }
    }
}

?>