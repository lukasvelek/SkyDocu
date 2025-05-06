<?php

namespace App\Modules\UserModule;

use App\Authenticators\ExternalSystemAuthenticator;
use App\Components\ProcessesGrid\ProcessesGrid;
use App\Constants\Container\ProcessGridViews;
use App\Core\Http\HttpRequest;

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

        $this->addExternalScript(APP_ABSOLUTE_DIR . 'resources\\js\\modules\\UserModule\\Home\\dashboard.js');
        $this->addScript('
            loadData("' . APP_URL . 'api/v1/", "' . $this->getSystemApiToken() . '");
        ');
    }
}

?>