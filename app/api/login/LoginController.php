<?php

namespace App\Api\Login;

use App\Api\ABaseApiClass;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Exceptions\ApiException;

/**
 * API login controller
 * 
 * @author Lukas Velek
 */
class LoginController extends ABaseApiClass {
    public function run(): JsonResponse {
        try {
            $this->startup();

            $systemId = $this->externalSystemAuthenticator->auth($this->getLogin(), $this->getPassword());

            $token = $this->externalSystemsManager->createOrGetToken($systemId);

            return new JsonResponse(['token' => $token]);
        } catch(AException $e) {
            return $this->convertExceptionToJson($e);
        }
    }

    /**
     * Returns login entered for authentication
     * 
     * @return string Login
     * @throws ApiException
     */
    private function getLogin() {
        $login = $this->get('login');

        if($login === null) {
            throw new ApiException('No login entered for authentication.');
        }

        return $login;
    }

    /**
     * Returns password entered for authentication
     * 
     * @return string Password
     * @throws ApiException
     */
    private function getPassword() {
        $password = $this->get('password');

        if($password === null) {
            throw new ApiException('No password entered for authentication.');
        }

        return $password;
    }
}

?>