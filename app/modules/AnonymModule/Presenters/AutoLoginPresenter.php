<?php

namespace App\Modules\AnonymModule;

class AutoLoginPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('AutoLoginPresenter', 'Autologin');
    }

    public function handleCheckLogin() {
        $fm = $this->httpGet('_fm');

        if($this->httpSessionGet('userId') === null) {
            $url = ['page' => 'AnonymModule:Home', 'action' => 'loginForm'];
        } else {
            
        }

        if($fm !== null) {
            $url['_fm'] = $fm;
        }

        $this->redirect($url);
    }

    private function calculateUserNextDestination() {
        
    }
}

?>