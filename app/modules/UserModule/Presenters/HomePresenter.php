<?php

namespace App\Modules\UserModule;

class HomePresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function renderDashboard() {
        $container = $this->app->containerManager->getContainerById($this->containerId);

        $code = null;
        if($container->getPermanentFlashMessage() !== null) {
            $code = $this->createFlashMessage('info', $container->getPermanentFlashMessage(), 0, false, true);
        }

        $this->template->permanent_flash_message = $code ?? '';

        $this->addExternalScript('resources/js/modules/UserModule/Home/dashboard.js');
        $this->addScript('
            loadData("api/v1/processes/instances/getWaitingForMe/", "' . $this->getSystemApiToken() . '");
        ');
    }
}

?>