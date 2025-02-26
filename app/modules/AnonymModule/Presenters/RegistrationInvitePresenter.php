<?php

namespace App\Modules\AnonymModule;

use App\Core\HashManager;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;

class RegistrationInvitePresenter extends AAnonymPresenter {
    private string $containerId;

    public function __construct() {
        parent::__construct('RegistrationInvitePresenter', 'Registration');
    }

    public function handleForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $containerId = $this->httpRequest->get('containerId');
            $inviteId = $this->httpRequest->get('inviteId');

            try {
                $data = [
                    'username' => $fr->username,
                    'fullname' => $fr->fullname,
                    'password' => HashManager::hashPassword($fr->password)
                ];

                if($fr->isset('email') && ($fr->email !== null)) {
                    $data['email'] = $fr->email;
                }

                $this->app->containerInviteRepository->beginTransaction(__METHOD__);
                
                $this->app->containerInviteManager->insertNewContainerInviteUsage($inviteId, $containerId, $data);

                $this->app->containerInviteRepository->commit($this->app->userManager->getServiceUserId(), __METHOD__);

                $this->redirect($this->createURL('success'));
            } catch(AException $e) {
                $e->saveToFile(true);
                $hash = $e->getHash();
                $this->app->containerInviteRepository->rollback(__METHOD__);

                $this->redirect($this->createURL('error', ['hash' => $hash]));
            }
        } else {
            $this->httpSessionSet('is_registering', '1');

            $inviteId = $this->httpRequest->get('inviteId');
            if($inviteId === null) {
                throw new RequiredAttributeIsNotSetException('inviteId');
            }

            try {
                $invite = $this->app->containerInviteManager->getInviteById($inviteId, false);

                if(strtotime($invite->dateValid) < time()) {
                    throw new GeneralException('Invite link has expired.', null, false);
                }

                $container = $this->app->containerManager->getContainerById($invite->containerId);
            } catch(AException $e) {
                $e->saveToFile(true); // explicitly save exception file
                $hash = $e->getHash();
                $params = [
                    'fmHash' => $hash
                ];
                $this->redirect($this->createFullURL('Anonym:Home', 'default', $params));
            }

            $this->containerId = $container->getId();

            $this->addScript('
                $("#password").on("change", function() {
                    const pv = $("#password").val();
                    const pcv = $("#passwordCheck").val();

                    if(pv != pcv) {
                        $("#lbl_password").css("color", "red");
                        $("#lbl_passwordCheck").css("color", "red");
                        $("#formSubmit").attr("disabled", true);
                        $("#formSubmit").attr("title", "Passwords do not match!");
                    } else {
                        $("#lbl_password").removeAttr("style");
                        $("#lbl_passwordCheck").removeAttr("style");
                        $("#formSubmit").removeAttr("disabled");
                        $("#formSubmit").removeAttr("title");
                    }
                });

                $("#passwordCheck").on("change", function() {
                    const pv = $("#password").val();
                    const pcv = $("#passwordCheck").val();

                    if(pcv != pv) {
                        $("#lbl_password").css("color", "red");
                        $("#lbl_passwordCheck").css("color", "red");
                        $("#formSubmit").attr("disabled", true);
                        $("#formSubmit").attr("title", "Passwords do not match!");
                    } else {
                        $("#lbl_password").removeAttr("style");
                        $("#lbl_passwordCheck").removeAttr("style");
                        $("#formSubmit").removeAttr("disabled");
                        $("#formSubmit").removeAttr("title");
                    }
                });
            ');
        }
    }

    public function renderForm() {}

    protected function createComponentRegistrationForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('form', ['inviteId' => $request->get('inviteId'), 'containerId' => $this->containerId]));

        $form->addTextInput('username', 'Username:')
            ->setRequired();
        $form->addTextInput('fullname', 'Fullname:')
            ->setRequired();
        $form->addEmailInput('email', 'Email:');

        $form->addPasswordInput('password', 'Password:')
            ->setRequired();
        $form->addPasswordInput('passwordCheck', 'Password again:')
            ->setRequired();

        $form->addSubmit('Register');

        return $form;
    }

    public function renderSuccess() {}

    public function renderError() {
        $this->template->hash = $this->httpRequest->get('hash');
    }
}

?>