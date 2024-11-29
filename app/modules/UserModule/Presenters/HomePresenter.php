<?php

namespace App\Modules\UserModule;

class HomePresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function handleDashboard() {
        $container = $this->app->containerManager->getContainerById($this->containerId);

        if($container->permanentFlashMessage !== null) {
            $code = $this->createFlashMessage('info', $container->permanentFlashMessage, 0, false, true);
            $this->saveToPresenterCache('permanentFlashMessage', $code);
        } else {
            $this->saveToPresenterCache('permanentFlashMessage', '');
        }
    }

    public function renderDashboard() {
        $this->template->permanent_flash_message = $this->loadFromPresenterCache('permanentFlashMessage');
    }
}

?>