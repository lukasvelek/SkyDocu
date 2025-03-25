<?php

namespace App\Managers\Container;

use App\Core\HashManager;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ExternalSystemLogRepository;
use App\Repositories\Container\ExternalSystemsRepository;

class ExternalSystemsManager extends AManager {
    private ExternalSystemsRepository $externalSystemsRepository;
    private ExternalSystemLogRepository $externalSystemLogRepository;

    /**
     * Class constructor
     */
    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        ExternalSystemsRepository $externalSystemsRepository,
        ExternalSystemLogRepository $externalSystemLogRepository
    ) {
        parent::__construct($logger, $entityManager);

        $this->externalSystemsRepository = $externalSystemsRepository;
        $this->externalSystemLogRepository = $externalSystemLogRepository;
    }

    /**
     * Creates a new external system
     * 
     * @param string $title Title
     * @param string $description Description
     */
    public function createNewExternalSystem(string $title, string $description) {
        $systemId = $this->createId(EntityManager::C_EXTERNAL_SYSTEMS);

        $login = $this->createUniqueHashForDb(32, EntityManager::C_EXTERNAL_SYSTEMS, 'login');
        $password = HashManager::hashPassword($login);

        if(!$this->externalSystemsRepository->insertNewExternalSystem($systemId, $title, $description, $login, $password)) {
            throw new GeneralException('Database error.');
        }
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
}

?>