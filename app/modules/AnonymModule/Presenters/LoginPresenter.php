<?php

namespace App\Modules\AnonymModule;

use App\Components\ContainerSelectionForm\ContainerSelectionForm;
use App\Constants\ContainerStatus;
use App\Constants\SessionNames;
use App\Constants\SystemGroups;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;

class LoginPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('LoginPresenter', 'Login');
    }

    public function handleLoginForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->app->userAuth->loginUser($fr->username, $fr->password);
                
                $this->app->logger->info('Logged in user #' . $this->httpSessionGet(SessionNames::USER_ID) . '.', __METHOD__);
                $this->redirect($this->createURL('checkContainers'));
            } catch(AException $e) {
                $this->flashMessage('Could not log in. Reason: ' . $e->getMessage(), 'error', 15);
                $this->redirect($this->createURL('loginForm'));
            }
        }
    }

    public function renderLoginForm() {}

    protected function createComponentLoginForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('loginForm'));

        $form->addEmailInput('email', 'Email:')
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

                if($container->getStatus() == ContainerStatus::NEW || $container->getStatus() == ContainerStatus::IS_BEING_CREATED || $container->getStatus() == ContainerStatus::NOT_RUNNING) {
                    continue;
                }

                $title = substr($group->title, 0, (strlen($group->title) - 8));
    
                $containers[] = [
                    'value' => $group->containerId,
                    'text' => $title
                ];
            } else {
                if($group->title == SystemGroups::SUPERADMINISTRATORS) {
                    $c = [
                        'value' => $group->title,
                        'text' => 'Superadministration'
                    ];
    
                    array_unshift($containers, $c);
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

            $lastContainer = $this->httpRequest->get('last');
            if($lastContainer !== null) {
                $params['lastContainer'] = $lastContainer;
            }

            $this->httpSessionSet('is_choosing_container', true);
            $this->redirect($this->createURL('containerForm', $params));
        }
    }

    public function handleContainerForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $this->httpSessionSet('container', $fr->container);
            
            if(isset($_SESSION[SessionNames::IS_CHOOSING_CONTAINER])) {
                unset($_SESSION[SessionNames::IS_CHOOSING_CONTAINER]);
            }
            
            if($fr->container == 'superadministrators') {
                $this->redirect($this->createFullURL('SuperAdmin:Home', 'home'));
            } else {
                $this->redirect($this->createFullURL('User:Home', 'dashboard'));
            }
        }
    }

    public function renderContainerForm() {}

    protected function createComponentContainerForm(HttpRequest $request) {
        $groups = $this->app->groupManager->getMembershipsForUser($this->getUserId());

        $containers = [];
        foreach($groups as $group) {
            if($group->containerId !== null) {
                $container = $this->app->containerManager->getContainerById($group->containerId);

                if($container->getStatus() == ContainerStatus::NEW || $container->getStatus() == ContainerStatus::IS_BEING_CREATED || $container->getStatus() == ContainerStatus::NOT_RUNNING) {
                    continue;
                }
            }

            if($group->title == 'superadministrators') {
                $c = [
                    'value' => $group->title,
                    'text' => 'Superadministration'
                ];

                array_unshift($containers, $c);
            } else if(str_ends_with($group->title, ' - users')) {
                $title = substr($group->title, 0, (strlen($group->title) - 8));

                $c = [
                    'value' => $group->containerId,
                    'text' => $title
                ];

                if($this->httpRequest->get('lastContainer') !== null) {
                    if($group->containerId == $this->httpRequest->get('lastContainer')) {
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
        $container = $this->httpSessionGet(SessionNames::CONTAINER);
        $this->httpSessionSet('container', null);
        $this->httpSessionSet('is_choosing_container', true);
        $this->httpSessionSet('current_document_folder_id', null);

        $this->redirect($this->createURL('checkContainers', ['last' => $container]));
    }
}

?>