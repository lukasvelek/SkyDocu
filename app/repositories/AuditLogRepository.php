<?php

namespace App\Repositories;

class AuditLogRepository extends ARepository {
    public function insertNewAuditLogEntry(
        string $entryId,
        ?string $containerId,
        string $userId,
        int $actionType,
        ?int $object1Type,
        ?int $object2Type,
        ?int $object3Type,
        string $description
    ) {
        $qb = $this->qb(__METHOD__);

        $keys = [
            'entryId',
            'userId',
            'actionType',
            'description'
        ];
        $values = [
            $entryId,
            $userId,
            $actionType,
            $description
        ];

        if($containerId !== null) {
            $keys[] = 'containerId';
            $values[] = $containerId;
        }
        if($object1Type !== null) {
            $keys[] = 'object1Type';
            $values[] = $object1Type;
        }
        if($object2Type !== null) {
            $keys[] = 'object2Type';
            $values[] = $object2Type;
        }
        if($object3Type !== null) {
            $keys[] = 'object3Type';
            $values[] = $object3Type;
        }

        $qb->insert('audit_log', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }
}

?>