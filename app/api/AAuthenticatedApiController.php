<?php

namespace App\Api;

use App\Authenticators\ExternalSystemAuthenticator;
use App\Constants\Container\ExternalSystemLogActionTypes;
use App\Constants\Container\ExternalSystemLogMessages;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\ContainerStatus;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;

/**
 * Common class for API endpoints authenticatable by token
 * 
 * @author Lukas Velek
 */
abstract class AAuthenticatedApiController extends AApiClass {
    protected string $token;
    protected string $systemId;

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
        $this->systemId = $tokenParts[2];
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
     * Logs PeeQL API
     * 
     * @param int $objectType Object type
     */
    protected function logPeeQL(int $objectType) {
        $entity = strtolower(ExternalSystemLogObjectTypes::toString($objectType));

        $message = sprintf(ExternalSystemLogMessages::PEEQL, $entity);

        $this->createLog($message, ExternalSystemLogActionTypes::PEEQL, $objectType);
    }

    /**
     * Logs API read
     * 
     * @param int $objectType Object type
     */
    protected function logRead(int $objectType) {
        $entity = strtolower(ExternalSystemLogObjectTypes::toString($objectType));

        $message = sprintf(ExternalSystemLogMessages::READ_DATA, $entity);

        $this->createLog($message, ExternalSystemLogActionTypes::READ, $objectType);
    }

    /**
     * Logs API create
     * 
     * @param int $objectType Object type
     */
    protected function logCreate(int $objectType) {
        $entity = strtolower(ExternalSystemLogObjectTypes::toString($objectType));

        $message = sprintf(ExternalSystemLogMessages::CREATE_DATA, $entity);

        $this->createLog($message, ExternalSystemLogActionTypes::CREATE, $objectType);
    }

    /**
     * Logs API update
     * 
     * @param int $objectType Object type
     */
    protected function logUpdate(int $objectType) {
        $entity = strtolower(ExternalSystemLogObjectTypes::toString($objectType));

        $message = sprintf(ExternalSystemLogMessages::UPDATE_DATA, $entity);

        $this->createLog($message, ExternalSystemLogActionTypes::UPDATE, $objectType);
    }

    /**
     * Logs API delete
     * 
     * @param int $objectType Object type
     */
    protected function logDelete(int $objectType) {
        $entity = strtolower(ExternalSystemLogObjectTypes::toString($objectType));

        $message = sprintf(ExternalSystemLogMessages::DELETE_DATA, $entity);

        $this->createLog($message, ExternalSystemLogActionTypes::DELETE, $objectType);
    }
    
    /**
     * Checks right
     * 
     * @param string $operationName Operation name
     */
    protected function checkRight(string $operationName): bool {
        $operations = $this->container->externalSystemsManager->getAllowedOperationsForSystem($this->systemId);

        $allowed = false;
        foreach($operations as $operation) {
            if($operation->operationName == $operationName) {
                $allowed = $operation->isEnabled;
            }
        }

        return $allowed;
    }
}

?>