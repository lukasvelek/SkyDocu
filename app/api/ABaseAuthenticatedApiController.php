<?php

namespace App\Api;

use App\Authenticators\ExternalSystemAuthenticator;
use App\Constants\Container\ExternalSystemLogActionTypes;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\ContainerStatus;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;

abstract class AAuthenticatedApiController extends AApiClass {
    protected string $token;
    protected string $systemId;

    protected function startup() {
        $this->getToken();

        parent::startup();

        $this->auth();

        $this->systemId = $this->externalSystemsManager->getExternalSystemByToken($this->token);
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

    /**
     * Creates a log entry
     * 
     * @param string $message Message
     * @param int $actionType Action type
     * @param int $objectType Object type
     */
    private function createLog(string $message, int $actionType, int $objectType) {
        try {
            $this->externalSystemsManager->createLogEntry($this->systemId, $message, $actionType, $objectType);
        } catch(AException $e) {
            throw new GeneralException('Could not save a log entry. Reason: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Logs API read
     * 
     * @param bool $readAll Read all
     * @param int $objectType Object type
     */
    protected function logRead(bool $readAll, int $objectType) {
        $entity = strtolower(ExternalSystemLogObjectTypes::toString($objectType));

        if($objectType == ExternalSystemLogObjectTypes::PROCESS) {
            if($readAll) {
                $entity .= 'es';
            }
        } else {
            if($readAll) {
                $entity .= 's';
            }
        }
        
        $message = sprintf('Reading %s %s.', ($readAll ? 'all' : 'single'), $entity);

        $this->createLog($message, ExternalSystemLogActionTypes::READ, $objectType);
    }

    /**
     * Logs API create
     * 
     * @param int $objectType Object type
     */
    protected function logCreate(int $objectType) {
        $message = sprintf('Creating %s.', strtolower(ExternalSystemLogObjectTypes::toString($objectType)));

        $this->createLog($message, ExternalSystemLogActionTypes::CREATE, $objectType);
    }

    /**
     * Logs API update
     * 
     * @param int $objectType Object type
     */
    protected function logUpdate(int $objectType) {
        $message = sprintf('Updating %s.', strtolower(ExternalSystemLogObjectTypes::toString($objectType)));

        $this->createLog($message, ExternalSystemLogActionTypes::UPDATE, $objectType);
    }

    /**
     * Logs API delete
     * 
     * @param int $objectType Object type
     */
    protected function logDelete(int $objectType) {
        $message = sprintf('Deleting %s.', strtolower(ExternalSystemLogObjectTypes::toString($objectType)));

        $this->createLog($message, ExternalSystemLogActionTypes::DELETE, $objectType);
    }
}

?>