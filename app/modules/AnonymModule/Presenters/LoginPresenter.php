<?php

namespace App\Modules\AnonymModule;

use App\Components\ContainerSelectionForm\ContainerSelectionForm;
use App\Constants\ContainerEnvironments;
use App\Constants\ContainerStatus;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormResponse;

class LoginPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('LoginPresenter', 'Login');
    }

    public function handleLoginForm(?FormResponse $fr = null) {
        if($fr !== null) {
            try {
                $this->app->userAuth->loginUser($fr->username, $fr->password);
                
                $this->app->logger->info('Logged in user #' . $this->httpSessionGet('userId') . '.', __METHOD__);
                $this->redirect($this->createURL('checkContainers'));
            } catch(AException $e) {
                $this->flashMessage('Could not log in due to internal error. Reason: ' . $e->getMessage(), 'error', 15);
                $this->redirect($this->createURL('loginForm'));
            }
        }
    }

    public function renderLoginForm() {
        $this->template->form = $this->loadFromPresenterCache('form');
        $this->template->title = 'Login';
    }

    protected function createComponentLoginForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('loginForm'));

        $form->addTextInput('username', 'Username:')
            ->setRequired();

        $form->addPasswordInput('password', 'Password:')
            ->setRequired();

        $form->addSubmit('Log in');

        return $form;
    }

    public function handleCheckContainers() {
        $groups = $this->app->groupManager->getMembershipsForUser($this->getUserId());

        $containers = [];
        foreach($groups as $group) {
            if($group->containerId !== null) {
                $container = $this->app->containerManager->getContainerById($group->containerId);

                if($container->status == ContainerStatus::NEW || $container->status == ContainerStatus::IS_BEING_CREATED || $container->status == ContainerStatus::NOT_RUNNING) {
                    continue;
                }

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
        }

        if(count($containers) == 0) {
            session_destroy();

            $this->flashMessage('User is not member of any group. Therefore login is not available.', 'error', 10);
            $this->redirect($this->createURL('loginForm'));
        } else if(count($containers) == 1) {
            $this->httpSessionSet('container', $containers[0]['value']);
            $this->redirect($this->createFullURL('Anonym:AutoLogin', 'checkLogin'));
        } else {
            $params = [];

            $lastContainer = $this->httpGet('last');
            if($lastContainer !== null) {
                $params['lastContainer'] = $lastContainer;
            }

            $this->httpSessionSet('is_choosing_container', true);
            $this->redirect($this->createURL('containerForm', $params));
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
        }
    }

    public function renderContainerForm() {
        $this->template->form = $this->loadFromPresenterCache('form');
    }

    protected function createComponentContainerForm(HttpRequest $request) {
        $groups = $this->app->groupManager->getMembershipsForUser($this->getUserId());

        $containers = [];
        foreach($groups as $group) {
            if($group->containerId !== null) {
                $container = $this->app->containerManager->getContainerById($group->containerId);

                if($container->status == ContainerStatus::NEW || $container->status == ContainerStatus::IS_BEING_CREATED || $container->status == ContainerStatus::NOT_RUNNING) {
                    continue;
                }
            }

            if($group->title == 'superadministrators') {
                $c = [
                    'value' => $group->title,
                    'text' => 'Superadministration'
                ];

                array_unshift($containers, $c);
            } else {
                $title = substr($group->title, 0, (strlen($group->title) - 8)) . ' (' . ContainerEnvironments::toString($container->environment) . ')';

                $c = [
                    'value' => $group->containerId,
                    'text' => $title
                ];

                if(array_key_exists('lastContainer', $this->httpRequest->query)) {
                    if($group->containerId == $this->httpRequest->query['lastContainer']) {
                        $c['selected'] = 'selected';
                    }
                }

                $containers[] = $c;
            }
        }

        $form = new ContainerSelectionForm($request);
        $form->setContainers($containers);
        $form->setAction($this->createURL('containerForm'));

        return $form;
    }

    public function handleSwitchContainer() {
        $container = $this->httpSessionGet('container');
        $this->httpSessionSet('container', null);
        $this->httpSessionSet('is_choosing_container', true);
        $this->httpSessionSet('current_document_folder_id', null);

        $this->redirect($this->createURL('checkContainers', ['last' => $container]));
    }
}

?>