<?php

namespace App\Modules\AnonymModule;

class HomePresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Topics');

        $this->setDefaultAction('default');
    }

    public function handleDefault() {
        $fmHash = $this->httpRequest->get('fmHash');

        $errorMessage = '';
        if($fmHash !== null) {
            $errorMessage = '<h3 style="color: red">An error occurred. Please contact administrator or the user that gave you this invite link. Don\'t forget to mention error ID: #' . $fmHash . '</h3>';
        }

        $this->saveToPresenterCache('errorMessage', $errorMessage);
    }

    public function renderDefault() {
        $this->template->error_message = $this->loadFromPresenterCache('errorMessage');
    }
}

?>