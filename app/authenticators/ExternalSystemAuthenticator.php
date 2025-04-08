<?php

namespace App\Authenticators;

use App\Exceptions\AException;
use App\Exceptions\ApiException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\Container\ExternalSystemsManager;

/**
 * ExternalSystemAuthenticator allows to authenticate an external system
 * 
 * @author Lukas Velek
 */
class ExternalSystemAuthenticator {
    private ExternalSystemsManager $externalSystemsManager;
    private Logger $logger;

    /**
     * Class constructor
     * 
     * @param ExternalSystemsManager $externalSystemsManager ExternalSystemsManager instance
     * @param Logger $logger Logger instance
     */
    public function __construct(ExternalSystemsManager $externalSystemsManager, Logger $logger) {
        $this->externalSystemsManager = $externalSystemsManager;
        $this->logger = $logger;
    }

    /**
     * Authenticates external system by token
     * 
     * @param string $token Token
     */
    public function authByToken(string $token) {
        try {
            $systemId = $this->externalSystemsManager->getExternalSystemByToken($token);

            $system = $this->externalSystemsManager->getExternalSystemById($systemId);

            if($system->isEnabled == false) {
                throw new GeneralException('External system is disabled.');
            }
        } catch(AException $e) {
            throw new ApiException('Could not authenticate external system. Reason: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Authenticates external system by login and password and returns the system ID
     * 
     * @param string $login Login
     * @param string $password Password
     */
    public function auth(string $login, string $password): string {
        try {
            $system = $this->externalSystemsManager->getExternalSystemByLogin($login);

            if(!password_verify($password, $system->password)) {
                throw new GeneralException('Bad credentials entered.');
            }

            if($system->isEnabled == false) {
                throw new GeneralException('External system is disabled.');
            }

            return $system->systemId;
        } catch(AException $e) {
            throw new ApiException('Could not authenticate external system. Reason: ' . $e->getMessage(), $e);
        }
    }
}

?>