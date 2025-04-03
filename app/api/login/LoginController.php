<?php

namespace App\Api\Login;

use App\Api\AApiClass;
use App\Authenticators\ExternalSystemAuthenticator;
use App\Core\Http\JsonResponse;

class LoginController extends AApiClass {
    private string $systemId;

    protected function startup() {
        $this->containerId = $this->get('containerId');

        parent::startup();
    }

    protected function run(): JsonResponse {
        $login = $this->get('login');
        $password = $this->get('password');

        $token = $this->loginUser($login, $password);

        $this->processToken($token);

        return new JsonResponse(['token' => $token]);
    }

    /**
     * Logins user and returns token
     * 
     * @param string $login Login
     * @param string $password Password
     */
    private function loginUser(string $login, string $password) {
        $externalSystemAuthenticator = new ExternalSystemAuthenticator($this->externalSystemsManager, $this->app->logger);

        $this->systemId = $externalSystemAuthenticator->auth($login, $password);

        return $this->externalSystemsManager->createOrGetToken($this->systemId);
    }

    /**
     * Processes token - adds other mandatory variables and encodes it to Base64
     * 
     * @param string &$token Token
     */
    private function processToken(string &$token) {
        $token = base64_encode($token . ';' . $this->containerId . ';' . $this->systemId);
    }
}

?>