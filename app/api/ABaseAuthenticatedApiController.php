<?php

namespace App\Api;

use App\Authenticators\ExternalSystemAuthenticator;
use App\Constants\ContainerStatus;
use App\Exceptions\GeneralException;

abstract class AAuthenticatedApiController extends AApiClass {
    protected string $token;

    protected function startup() {
        $this->getToken();

        parent::startup();

        $this->auth();
    }

    /**
     * Gets token data
     */
    private function getToken() {
        $token = base64_decode($this->get('token'));

        $tokenParts = explode(';', $token);

        $this->token = $tokenParts[0];
        $this->containerId = $tokenParts[1];
    }

    /**
     * Authenticates the external system
     */
    private function auth() {
        $externalSystemAuthenticator = new ExternalSystemAuthenticator($this->externalSystemsManager, $this->app->logger);
        $externalSystemAuthenticator->authByToken($this->token);

        $container = $this->app->containerManager->getContainerById($this->containerId, true);

        if(!in_array($container->getStatus(), [ContainerStatus::NOT_RUNNING, ContainerStatus::RUNNING])) {
            throw new GeneralException('Container has incorrect status.');
        }
    }
}

?>