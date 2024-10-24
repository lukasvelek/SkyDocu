<?php

namespace App\Modules\AnonymModule;

use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;

class LoginPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('LoginPresenter', 'Login');
    }

    public function handleLoginForm(?FormResponse $fr = null) {
        if($this->httpGet('isSubmit') == 'true') {
            try {
                $this->app->userAuth->loginUser($fr->username, $fr->password);
                
                $this->app->logger->info('Logged in user #' . $this->httpSessionGet('userId') . '.', __METHOD__);
                $this->redirect(['page' => 'Anonym:AutoLogin', 'action' => 'checkLogin']);
            } catch(AException $e) {
                $this->flashMessage('Could not log in due to internal error. Reason: ' . $e->getMessage(), 'error', 15);
                $this->redirect($this->createURL('loginForm'));
            }
        } else {
            $fb = new FormBuilder();
        
            $fb ->setAction(['page' => 'Anonym:Login', 'action' => 'loginForm', 'isSubmit' => 'true'])
                ->addTextInput('username', 'Username:', null, true)
                ->addPassword('password', 'Password:', null, true)
                ->addSubmit('Log in')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderLoginForm() {
        $this->template->form = $this->loadFromPresenterCache('form');
        $this->template->title = 'Login';
    }
}

?>