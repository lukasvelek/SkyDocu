<?php

namespace App\Api;

use App\Authenticators\ExternalSystemAuthenticator;
use App\Core\Application;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Exceptions\ApiException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\Container\ExternalSystemsManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ExternalSystemLogRepository;
use App\Repositories\Container\ExternalSystemsRepository;
use App\Repositories\Container\ExternalSystemTokenRepository;
use App\Repositories\ContentRepository;

/**
 * API login controller
 * 
 * @author Lukas Velek
 */
class ApiLogin extends ABaseApiClass {
    public function __construct(Application $app) {
        parent::__construct($app);
    }

    public function run(): JsonResponse {
        try {
            $this->startup($this->getContainerId());

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
        if(!array_key_exists('login', $this->getPostData())) {
            throw new GeneralException('Login is not set.');
        }

        $login = $this->getPostData()['login'];

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
        if(!array_key_exists('password', $this->getPostData())) {
            throw new GeneralException('Password is not set.');
        }

        $password = $this->getPostData()['password'];

        if($password === null) {
            throw new ApiException('No password entered for authentication.');
        }

        return $password;
    }
}

?>