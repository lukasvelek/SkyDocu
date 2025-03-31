<?php

namespace App\Api\Login;

use App\Api\AApiClass;
use App\Authenticators\ExternalSystemAuthenticator;
use App\Core\Http\JsonResponse;

class LoginController extends AApiClass {
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

        $systemId = $externalSystemAuthenticator->auth($login, $password);

        return $this->externalSystemsManager->createOrGetToken($systemId);
    }

    /**
     * Processes token
     * 
     * @param string &$token Token
     */
    private function processToken(string &$token) {
        $token = base64_encode($token . ';' . $this->containerId);
    }
}

?>