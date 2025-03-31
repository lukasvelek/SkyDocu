<?php

namespace App\Managers\Container;

use App\Constants\Container\ExternalSystemLogActionTypes;
use App\Constants\Container\ExternalSystemLogMessages;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\Container\ExternalSystemRightsOperations;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\HashManager;
use App\Exceptions\AException;
use App\Exceptions\ApiException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ExternalSystemLogRepository;
use App\Repositories\Container\ExternalSystemRightsRepository;
use App\Repositories\Container\ExternalSystemsRepository;
use App\Repositories\Container\ExternalSystemTokenRepository;

/**
 * ExternalSystemsManager contains useful methods for managing external systems
 * 
 * @author Lukas Velek
 */
class ExternalSystemsManager extends AManager {
    private ExternalSystemsRepository $externalSystemsRepository;
    private ExternalSystemLogRepository $externalSystemLogRepository;
    private ExternalSystemTokenRepository $externalSystemTokenRepository;
    private ExternalSystemRightsRepository $externalSystemRightsRepository;

    /**
     * Class constructor
     */
    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        ExternalSystemsRepository $externalSystemsRepository,
        ExternalSystemLogRepository $externalSystemLogRepository,
        ExternalSystemTokenRepository $externalSystemTokenRepository,
        ExternalSystemRightsRepository $externalSystemRightsRepository
    ) {
        parent::__construct($logger, $entityManager);

        $this->externalSystemsRepository = $externalSystemsRepository;
        $this->externalSystemLogRepository = $externalSystemLogRepository;
        $this->externalSystemTokenRepository = $externalSystemTokenRepository;
        $this->externalSystemRightsRepository = $externalSystemRightsRepository;
    }

    /**
     * Creates a new external system
     * 
     * @param string $title Title
     * @param string $description Description
     */
    public function createNewExternalSystem(string $title, string $description, string $password) {
        $systemId = $this->createId(EntityManager::C_EXTERNAL_SYSTEMS);

        $login = $this->createUniqueHashForDb(32, EntityManager::C_EXTERNAL_SYSTEMS, 'login');
        $password = HashManager::hashPassword($password);

        if(!$this->externalSystemsRepository->insertNewExternalSystem($systemId, $title, $description, $login, $password)) {
            throw new GeneralException('Database error.');
        }

        $this->allowAllExternalSystemOperations($systemId);
    }

    /**
     * Enables external system
     * 
     * @param string $systemId System ID
     */
    public function enableExternalSystem(string $systemId) {
        $this->updateExternalSystem($systemId, ['isEnabled' => '1']);
    }

    /**
     * Disables external system
     * 
     * @param string $systemId System ID
     */
    public function disableExternalSystem(string $systemId) {
        $this->updateExternalSystem($systemId, ['isEnabled' => '0']);
    }

    /**
     * Updates external system
     * 
     * @param string $systemId System ID
     * @param array $data Data
     */
    public function updateExternalSystem(string $systemId, array $data) {
        if(!$this->externalSystemsRepository->updateExternalSystem($systemId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Returns a database row for given system ID
     * 
     * @param string $systemId System ID
     */
    public function getExternalSystemById(string $systemId): DatabaseRow {
        $row = $this->externalSystemsRepository->getExternalSystemById($systemId);

        if($row === null) {
            throw new NonExistingEntityException('External system with ID \'' . $systemId . '\' does not exist.');
        }

        return DatabaseRow::createFromDbRow($row);
    }

    /**
     * Returns available token or throws exception if none exists
     * 
     * @param string $systemId System ID
     */
    public function getAvailableTokenForExternalSystem(string $systemId): string {
        $row = $this->externalSystemTokenRepository->getAvailableTokenForExternalSystem($systemId);

        if($row === null) {
            throw new GeneralException('System has no available token.');
        }

        return $row['token'];
    }

    /**
     * Returns external system by token
     * 
     * @param string $token Token
     */
    public function getExternalSystemByToken(string $token): string {
        $row = $this->externalSystemTokenRepository->getSystemByToken($token);

        if($row === null) {
            throw new GeneralException('Token does not exist or is expired.');
        }

        return $row['systemId'];
    }

    /**
     * Returns external system by login
     * 
     * @param string $login Login
     */
    public function getExternalSystemByLogin(string $login): DatabaseRow {
        $row = $this->externalSystemsRepository->getExternalSystemByLogin($login);

        if($row === null) {
            throw new GeneralException('Bad credentials entered.');
        }

        return DatabaseRow::createFromDbRow($row);
    }

    /**
     * Creates a new token for external system
     * 
     * @param string $systemId System ID
     */
    public function createNewToken(string $systemId): string {
        $tokenId = $this->createId(EntityManager::C_EXTERNAL_SYSTEM_TOKENS);
        $token = HashManager::createHash(256, false);
        $dateValidUntil = new DateTime();
        $dateValidUntil->modify('+1h');
        $dateValidUntil = $dateValidUntil->getResult();

        if(!$this->externalSystemTokenRepository->insertNewExternalSystemToken($tokenId, $systemId, $token, $dateValidUntil)) {
            throw new GeneralException('Database error.');
        }

        return $token;
    }

    /**
     * Tries to get an existing token or creates a new one
     * 
     * @param string $systemId System ID
     * @param bool $createLogEntry Create log entry
     */
    public function createOrGetToken(string $systemId, bool $createLogEntry = true): string {
        $token = null;

        try {
            $token = $this->getAvailableTokenForExternalSystem($systemId);

            if($createLogEntry) {
                $this->createLogEntry($systemId, ExternalSystemLogMessages::GET_EXISTING_TOKEN, ExternalSystemLogActionTypes::LOGIN, ExternalSystemLogObjectTypes::EXTERNAL_SYSTEM);
            }
        } catch(AException $e) {
            $token = $this->createNewToken($systemId);

            if($createLogEntry) {
                $this->createLogEntry($systemId, ExternalSystemLogMessages::CREATE_NEW_TOKEN, ExternalSystemLogActionTypes::LOGIN, ExternalSystemLogObjectTypes::EXTERNAL_SYSTEM);
            }
        }

        return $token;
    }

    /**
     * Creates a log entry
     * 
     * @param string $systemId System ID
     * @param string $message Message
     * @param string $actionType Action type
     * @param string $objectType Object type
     */
    public function createLogEntry(string $systemId, string $message, string $actionType, string $objectType) {
        $entryId = $this->createId(EntityManager::C_EXTERNAL_SYSTEM_LOG);

        if(!$this->externalSystemLogRepository->insertNewLogEntry($entryId, $systemId, $message, $actionType, $objectType)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Returns all allowed operations for system
     * 
     * @param string $systemId System ID
     */
    public function getAllowedOperationsForSystem(string $systemId): array {
        $qb = $this->externalSystemRightsRepository->composeQueryForExternalSystemRights();
        $qb->andWhere('systemId = ?', [$systemId])
            ->execute();

        $operations = [];
        while($row = $qb->fetchAssoc()) {
            $operations[] = DatabaseRow::createFromDbRow($row);
        }

        return $operations;
    }

    /**
     * Allows external system operation
     * 
     * @param string $systemId System ID
     * @param string $operationName Operation name
     */
    public function allowExternalSystemOperation(string $systemId, string $operationName) {
        if(!$this->externalSystemRightsRepository->updateExternalSystemOperation($systemId, $operationName, true)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Disallows external system operation
     * 
     * @param string $systemId System ID
     * @param string $operationName Operation name
     */
    public function disallowExternalSystemOperation(string $systemId, string $operationName) {
        if(!$this->externalSystemRightsRepository->updateExternalSystemOperation($systemId, $operationName, false)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Allows all external system operations
     * 
     * @param string $systemId System ID
     */
    public function allowAllExternalSystemOperations(string $systemId) {
        foreach(ExternalSystemRightsOperations::getAll() as $operation => $text) {
            $rightId = $this->createId(EntityManager::C_EXTERNAL_SYSTEM_RIGHTS);

            if(!$this->externalSystemRightsRepository->insertAllowedExternalSystemOperation($rightId, $systemId, $operation)) {
                throw new GeneralException('Database error.');
            }
        }
    }

    /**
     * Removes all external system rights
     * 
     * @param string $systemId System ID
     */
    public function removeAllExternalSystemRights(string $systemId) {
        if(!$this->externalSystemRightsRepository->deleteAllExternalSystemOperations($systemId)) {
            throw new GeneralException('Database error');
        }
    }

    /**
     * Deletes external system
     * 
     * @param string $systemId System ID
     */
    public function deleteExternalSystem(string $systemId) {
        // delete rights
        $this->removeAllExternalSystemRights($systemId);

        // delete tokens
        if(!$this->externalSystemTokenRepository->deleteExternalSystemTokens($systemId)) {
            throw new GeneralException('Database error.');
        }

        // delete logs
        if(!$this->externalSystemLogRepository->deleteExternalSystemLogs($systemId)) {
            throw new GeneralException('Database error.');
        }

        // delete system
        if(!$this->externalSystemsRepository->deleteExternalSystem($systemId)) {
            throw new GeneralException('Database error.');
        }
    }
}

?>