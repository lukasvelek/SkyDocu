<?php

namespace App\Modules\AnonymModule;

use App\Core\Http\FormRequest;

class ForgottenPasswordPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('ForgottenPasswordPresenter', 'Forgotten password');
    }

    public function renderForm() {

    }

    protected function createComponentForgottenPasswordForm() {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('formSubmit'));

        $form->addEmailInput('email', 'Email:')
            ->setRequired();

        $form->addSubmit('Submit');

        $form->addButton('Log in')
            ->setOnClick('location.href = \'' . $this->createFullURLString('Anonym:Login', 'loginForm') . '\';');

        return $form;
    }

    public function handleFormSubmit(FormRequest $fr) {
        // to do: implement sending emails
    }
}