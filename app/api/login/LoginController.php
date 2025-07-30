<?php

namespace App\Api\Login;

use App\Api\AApiClass;
use App\Api\IAPITokenProcessing;
use App\Authenticators\ExternalSystemAuthenticator;
use App\Core\Http\JsonResponse;
use App\Entities\ApiTokenEntity;

class LoginController extends AApiClass implements IAPITokenProcessing {
    private string $systemId;

    protected function startup() {
        $this->containerId = $this->get('containerId');

        parent::startup();
    }

    protected function run(): JsonResponse {
        $login = $this->get('login');
        $password = $this->get('password');

        $token = $this->loginUser($login, $password);
        $token = $this->processToken($token);

        return new JsonResponse(['token' => $token]);
    }

    /**
     * Logins user and returns token
     * 
     * @param string $login Login
     * @param string $password Password
     */
    private function loginUser(string $login, string $password) {
        $externalSystemAuthenticator = new ExternalSystemAuthenticator($this->app->externalSystemsManager, $this->app->logger);

        $this->systemId = $externalSystemAuthenticator->auth($login, $password);

        return $this->app->externalSystemsManager->createOrGetToken($this->systemId);
    }

    public function processToken(string $token): string {
        $entity = ApiTokenEntity::createNewEntity($token, $this->containerId, $this->systemId);
        return $entity->convertToToken();
    }
}

?>