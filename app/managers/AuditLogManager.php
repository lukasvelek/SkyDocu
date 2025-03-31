<?php

namespace App\Managers;

use App\Constants\AuditLogActionTypes;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\AuditLogRepository;

/**
 * AuditLogManager is used for managing audit log
 * 
 * @author Lukas Velek
 */
class AuditLogManager extends AManager {
    private AuditLogRepository $auditLogRepository;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     * @param EntityManager $entityManager EntityManager instance
     * @param AuditLogRepository $auditLogRepository AuditLogRepository instance
     */
    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        AuditLogRepository $auditLogRepository
    ) {
        parent::__construct($logger, $entityManager);

        $this->auditLogRepository = $auditLogRepository;
    }

    /**
     * Creates a new audit log entry
     * 
     * @param ?string $containerId Container ID
     * @param string $userId User ID
     * @param int $actionType Action type
     * @param ?int $object1Type Object 1 type
     * @param ?int $object2Type Object 2 type
     * @param ?int $object3Type Object 3 type
     * @param string $description Description
     */
    private function createAuditLogEntry(
        ?string $containerId,
        string $userId,
        int $actionType,
        ?int $object1Type,
        ?int $object2Type,
        ?int $object3Type,
        string $description
    ) {
        $entryId = $this->createId(EntityManager::AUDIT_LOG);

        if(!$this->auditLogRepository->insertNewAuditLogEntry(
            $entryId,
            $containerId,
            $userId,
            $actionType,
            $object1Type,
            $object2Type,
            $object3Type,
            $description
        )) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Creates a new read audit log entry
     * 
     * @param ?string $containerId Container ID
     * @param string $userId User ID
     * @param ?int $object1Type Object 1 type
     * @param ?int $object2Type Object 2 type
     * @param ?int $object3Type Object 3 type
     */
    public function createReadAuditLogEntry(
        ?string $containerId,
        string $userId,
        ?int $object1Type,
        ?int $object2Type,
        ?int $object3Type
    ) {
        $this->createAuditLogEntry(
            $containerId,
            $userId,
            AuditLogActionTypes::READ,
            $object1Type,
            $object2Type,
            $object3Type,
            sprintf('User "%s" read given information.', $userId)
        );
    }

    /**
     * Create a new create audit log entry
     * 
     * @param ?string $containerId Container ID
     * @param string $userId User ID
     * @param ?int $object1Type Object 1 type
     * @param ?int $object2Type Object 2 type
     * @param ?int $object3Type Object 3 type
     */
    public function createCreateAuditLogEntry(
        ?string $containerId,
        string $userId,
        ?int $object1Type,
        ?int $object2Type,
        ?int $object3Type
    ) {
        $this->createAuditLogEntry(
            $containerId,
            $userId,
            AuditLogActionTypes::CREATE,
            $object1Type,
            $object2Type,
            $object3Type,
            sprintf('User "%s" create given object.', $userId)
        );
    }
}

?>