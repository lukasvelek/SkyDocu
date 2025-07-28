<?php

namespace App\Managers;

use App\Core\DB\DatabaseRow;
use App\Core\HashManager;
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
}