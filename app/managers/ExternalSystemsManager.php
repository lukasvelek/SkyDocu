<?php

namespace App\Managers;

use App\Constants\ExternalSystemLogActionTypes;
use App\Constants\ExternalSystemLogMessages;
use App\Constants\ExternalSystemLogObjectTypes;
use App\Constants\ExternalSystemRightsOperations;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\HashManager;
use App\Entities\ExternalSystemTokenEntity;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\ExternalSystemsLogRepository;
use App\Repositories\ExternalSystemsRepository;
use App\Repositories\ExternalSystemsRightsRepository;
use App\Repositories\ExternalSystemsTokenRepository;

/**
 * ExternalSystemsManager contains high-level API methods for external systems
 * 
 * @author Lukas Velek
 */
class ExternalSystemsManager extends AManager {
    private ExternalSystemsRepository $externalSystemsRepository;
    private ExternalSystemsTokenRepository $externalSystemsTokenRepository;
    private ExternalSystemsLogRepository $externalSystemsLogRepository;
    private ExternalSystemsRightsRepository $externalSystemsRightsRepository;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     * @param ExternalSystemsRepository $externalSystemsRepository instance
     * @param ExternalSystemsLogRepository $externalSystemsLogRepository ExternalSystemsLogRepository instance
     * @param ExternalSystemsTokenRepository $externalSystemsTokenRepository ExternalSystemsTokenRepository instance
     * @param ExternalSystemsRightsRepository $externalSystemsRightsRepository ExternalSystemsRightsRepository instance
     */
    public function __construct(
        Logger $logger,
        ExternalSystemsRepository $externalSystemsRepository,
        ExternalSystemsLogRepository $externalSystemsLogRepository,
        ExternalSystemsTokenRepository $externalSystemsTokenRepository,
        ExternalSystemsRightsRepository $externalSystemsRightsRepository
    ) {
        parent::__construct($logger);

        $this->externalSystemsRepository = $externalSystemsRepository;
        $this->externalSystemsLogRepository = $externalSystemsLogRepository;
        $this->externalSystemsTokenRepository = $externalSystemsTokenRepository;
        $this->externalSystemsRightsRepository = $externalSystemsRightsRepository;
    }

    /**
     * Creates a new external system
     * 
     * @param string $title Title
     * @param string $description Description
     * @param string $password Password
     * @param ?string $containerId Container ID or null
     */
    public function createNewExternalSystem(
        string $title,
        string $description,
        string $password,
        ?string $containerId = null
    ) {
        $systemId = $this->createId();

        $login = $this->createId();
        $password = HashManager::hashPassword($password);

        $data = [
            'systemId' => $systemId,
            'title' => $title,
            'description' => $description,
            'login' => $login,
            'password' => $password,
            'isEnabled' => 1
        ];

        if($containerId !== null) {
            $data['containerId'] = $containerId;
        }

        if(!$this->externalSystemsRepository->createNewExternalSystem($data)) {
            throw new GeneralException('Database error.');
        }

        $this->createExternalSystemRights($systemId, $containerId);
    }

    /**
     * Updates external system
     * 
     * @param string $systemId System ID
     * @param array $data Data array
     * @throws GeneralException
     */
    public function updateExternalSystem(
        string $systemId,
        array $data
    ) {
        if(!$this->externalSystemsRepository->updateExternalSystem($systemId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Updates external systems in bulk
     * 
     * @param array $systemIds System IDs
     * @param array $data Data array
     * @throws GeneralException
     */
    public function bulkUpdateExternalSystems(
        array $systemIds,
        array $data
    ) {
        if(!$this->externalSystemsRepository->bulkUpdateExternalSystems($systemIds, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Enables external system
     * 
     * @param string $systemId System ID
     */
    public function enableExternalSystem(string $systemId) {
        $this->updateExternalSystem($systemId, ['isEnabled' => 1]);
    }

    /**
     * Disables external system
     * 
     * @param string $systemId System ID
     */
    public function disableExternalSystem(string $systemId) {
        $this->updateExternalSystem($systemId, ['isEnabled' => 0]);
    }

    /**
     * Returns external system by ID
     * 
     * @param string $systemId System ID
     * @throws NonExistingEntityException
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
     * @param ?string $containerId Container ID
     */
    public function getAvailableTokenForExternalSystem(
        string $systemId,
        ?string $containerId
    ): ExternalSystemTokenEntity {
        $row = $this->externalSystemsTokenRepository->getAvailableTokenForExternalSystem($systemId, $containerId);

        if($row === null) {
            throw new GeneralException('System has no available token.');
        }

        return ExternalSystemTokenEntity::getFromGeneratedToken($row['token']);
    }

    /**
     * Returns external system ID by token
     * 
     * @param string $token Token
     */
    public function getExternalSystemByToken(string $token): string {
        $row = $this->externalSystemsTokenRepository->getSystemByToken($token);

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
     * Creates a new token
     * 
     * @param string $systemId System ID
     * @param ?string $containerId Container ID
     */
    public function createNewToken(
        string $systemId,
        ?string $containerId = null
    ): ExternalSystemTokenEntity {
        $tokenId = $this->createId();

        $hash = HashManager::createHash(256, true);

        $dateValidUntil = new DateTime();
        $dateValidUntil->modify('+1h');
        $dateValidUntil = $dateValidUntil->getResult();

        $token = new ExternalSystemTokenEntity(
            $tokenId,
            $hash,
            $dateValidUntil
        );

        $data = [
            'systemId' => $systemId,
            'token' => $token->generateToken(),
            'dateValidUntil' => $dateValidUntil,
            'tokenId' => $tokenId
        ];

        if($containerId !== null) {
            $data['containerId'] = $containerId;
        }

        if(!$this->externalSystemsTokenRepository->insertNewToken($data)) {
            throw new GeneralException('Database error.');
        }

        return $token;
    }

    /**
     * Tries to get an existing token or creates a new token
     * 
     * @param string $systemId System ID
     * @param ?string $containerId Container ID
     * @param bool $createLogEntry Create log entry
     */
    public function createOrGetToken(
        string $systemId,
        ?string $containerId = null,
        bool $createLogEntry = true,
        bool $force = false
    ): ExternalSystemTokenEntity {
        $token = null;

        try {
            if($force) {
                throw new GeneralException('Force create new token.');
            }

            $token = $this->getAvailableTokenForExternalSystem(
                $systemId,
                $containerId
            );

            if($createLogEntry) {
                $this->createLogEntry(
                    $systemId,
                    ExternalSystemLogMessages::GET_EXISTING_TOKEN,
                    ExternalSystemLogActionTypes::LOGIN,
                    ExternalSystemLogObjectTypes::EXTERNAL_SYSTEM,
                    $containerId
                );
            }
        } catch(AException $e) {
            $token = $this->createNewToken(
                $systemId,
                $containerId
            );

            if($createLogEntry) {
                $this->createLogEntry(
                    $systemId,
                    ExternalSystemLogMessages::CREATE_NEW_TOKEN,
                    ExternalSystemLogActionTypes::LOGIN,
                    ExternalSystemLogObjectTypes::EXTERNAL_SYSTEM,
                    $containerId
                );
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
     * @param ?string $containerId Container ID or null
     */
    public function createLogEntry(
        string $systemId,
        string $message,
        string $actionType,
        string $objectType,
        ?string $containerId = null
    ) {
        $entryId = $this->createId();

        $data = [
            'entryId' => $entryId,
            'systemId' => $systemId,
            'message' => $message,
            'actionType' => $actionType,
            'objectType' => $objectType
        ];

        if($containerId !== null) {
            $data['containerId'] = $containerId;
        }

        if(!$this->externalSystemsLogRepository->insertNewLogEntry($data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Deletes external system
     * 
     * @param string $systemId System ID
     * @throws GeneralException
     */
    public function deleteExternalSystem(string $systemId) {
        // delete tokens
        if(!$this->externalSystemsTokenRepository->deleteTokensForSystem($systemId)) {
            throw new GeneralException('Database error.');
        }

        // delete rights
        if(!$this->externalSystemsRightsRepository->deleteOperationRightsForSystem($systemId)) {
            throw new GeneralException('Database error.');
        }

        // delete system
        if(!$this->externalSystemsRepository->deleteExternalSystem($systemId)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Deletes all external systems for container
     * 
     * @param string $containerId Container ID
     * @throws GeneralException
     */
    public function deleteExternalSystemsForCotnainer(string $containerId) {
        $qb = $this->externalSystemsRepository->composeQueryForExternalSystemsForContainer($containerId);

        $qb->execute();

        $systemIds = [];
        while($row = $qb->fetchAssoc()) {
            $systemIds[] = $row['systemId'];
        }

        foreach($systemIds as $systemId) {
            $this->deleteExternalSystem($systemId);
        }
    }

    /**
     * Returns an array of operations for given system
     * 
     * @param string $systemId System ID
     */
    public function getOperationsForSystem(string $systemId): array {
        $qb = $this->externalSystemsRightsRepository->composeQueryForExternalSystemRights();
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
     * @param ?string $containerId Container ID
     * @param string $operationName Operation name
     * @throws GeneralException
     */
    public function allowExternalSystemOperation(string $systemId, ?string $containerId, string $operationName) {
        $this->updateExternalSystemOperationForSystem(
            $systemId,
            $containerId,
            $operationName
        );
    }

    /**
     * Denies external system operation
     * 
     * @param string $systemId System ID
     * @param ?string $containerId Container ID
     * @param string $operationName Operation name
     * @throws GeneralException
     */
    public function denyExternalSystemOperation(string $systemId, ?string $containerId, string $operationName) {
        $this->updateExternalSystemOperationForSystem(
            $systemId,
            $containerId,
            $operationName,
            false
        );
    }

    /**
     * Updates external system operation for system
     * 
     * @param string $systemId System ID
     * @param ?string $containerId Container ID
     * @param string $operationName Operation name
     * @param bool $allowed Allower or not
     * @throws GeneralException
     */
    private function updateExternalSystemOperationForSystem(string $systemId, ?string $containerId, string $operationName, bool $allowed = true) {
        if(!$this->externalSystemsRightsRepository->updateOperationRight($systemId, $operationName, [
            'isEnabled' => ($allowed ? 1 : 0)
        ])) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Creates external system rights
     * 
     * @param string $systemId System ID
     * @param ?string $containerId Container ID
     */
    private function createExternalSystemRights(string $systemId, ?string $containerId) {
        $operations = array_keys(ExternalSystemRightsOperations::getAll());

        foreach($operations as $operation) {
            $rightId = $this->createId();
            
            if(!$this->externalSystemsRightsRepository->insertOperationRight([
                'systemId' => $systemId,
                'rightId' => $rightId,
                'operationName' => $operation,
                'isEnabled' => 0,
                'containerId' => $containerId
            ])) {
                throw new GeneralException('Database error.');
            }
        }
    }
}