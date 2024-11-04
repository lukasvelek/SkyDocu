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
                $this->redirect(['page' => 'Anonym:Login', 'action' => 'checkContainers']);
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

    public function handleCheckContainers() {
        $groups = $this->app->groupManager->getMembershipsForUser($this->getUserId());

        if(count($groups) == 0) {
            session_destroy();

            $this->flashMessage('User is not member of any group. Therefore login is not available.', 'error', 10);
            $this->redirect($this->createURL('loginForm'));
        } else if(count($groups) == 1) {
            $this->redirect($this->createFullURL('Anonym:AutoLogin', 'checkLogin'));
        } else {
            $this->httpSessionSet('is_choosing_container', true);
            $this->redirect($this->createURL('containerForm'));
        }
    }

    public function handleContainerForm(?FormResponse $fr = null) {
        if($fr !== null) {
            $this->httpSessionSet('container', $fr->container);
            
            if(isset($_SESSION['is_choosing_container'])) {
                unset($_SESSION['is_choosing_container']);
            }
            
            if($fr->container == 'superadministrators') {
                $this->redirect($this->createFullURL('SuperAdmin:Home', 'home'));
            } else {
                $this->redirect($this->createFullURL('User:Home', 'dashboard'));
            }
        } else {
            $groups = $this->app->groupManager->getMembershipsForUser($this->getUserId());

            $containers = [];
            foreach($groups as $group) {
                if($group->title == 'superadministrators') {
                    $c = [
                        'value' => $group->title,
                        'text' => 'Superadministration'
                    ];

                    array_unshift($containers, $c);
                } else {
                    $title = substr($group->title, 0, (strlen($group->title) - 8));

                    $containers[] = [
                        'value' => $group->containerId,
                        'text' => $title
                    ];
                }
            }

            $form = new FormBuilder();

            $form->setMethod()
                ->setAction($this->createURL('containerForm'))
                ->addSelect('container', 'Container:', $containers, true)
                ->addSubmit('Select')
            ;

            $this->saveToPresenterCache('form', $form);
        }
    }

    public function renderContainerForm() {
        $this->template->form = $this->loadFromPresenterCache('form');
    }
}

?>