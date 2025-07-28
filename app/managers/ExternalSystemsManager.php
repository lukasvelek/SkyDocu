<?php

namespace App\Managers;

use App\Constants\ExternalSystemLogActionTypes;
use App\Constants\ExternalSystemLogMessages;
use App\Constants\ExternalSystemLogObjectTypes;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\HashManager;
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
     * @param EntityManager $entityManager EntityManager instance
     * @param ExternalSystemsRepository $externalSystemsRepository instance
     * @param ExternalSystemsLogRepository $externalSystemsLogRepository ExternalSystemsLogRepository instance
     * @param ExternalSystemsTokenRepository $externalSystemsTokenRepository ExternalSystemsTokenRepository instance
     * @param ExternalSystemsRightsRepository $externalSystemsRightsRepository ExternalSystemsRightsRepository instance
     */
    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        ExternalSystemsRepository $externalSystemsRepository,
        ExternalSystemsLogRepository $externalSystemsLogRepository,
        ExternalSystemsTokenRepository $externalSystemsTokenRepository,
        ExternalSystemsRightsRepository $externalSystemsRightsRepository
    ) {
        parent::__construct($logger, $entityManager);

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
        $systemId = $this->createId(EntityManager::EXTERNAL_SYSTEMS);

        $login = $this->createUniqueHashForDb(32, EntityManager::EXTERNAL_SYSTEMS, 'login');
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
    ): string {
        $row = $this->externalSystemsTokenRepository->getAvailableTokenForExternalSystem($systemId);

        if($row === null) {
            throw new GeneralException('System has no available token.');
        }

        return $row['token'];
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
    ): string {
        $tokenId = $this->createId(EntityManager::EXTERNAL_SYSTEM_TOKENS);

        $token = HashManager::createHash(256, true);

        $dateValidUntil = new DateTime();
        $dateValidUntil->modify('+1h');
        $dateValidUntil = $dateValidUntil->getResult();

        $data = [
            'systemId' => $systemId,
            'token' => $token,
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
        bool $createLogEntry = true
    ) {
        $token = null;

        try {
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
        $entryId = $this->createId(EntityManager::EXTERNAL_SYSTEM_LOG);

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
}