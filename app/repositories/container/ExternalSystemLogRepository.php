<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class ExternalSystemLogRepository extends ARepository {
    public function composeQueryForExternalSystemLog() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_system_log');

        return $qb;
    }

    public function insertNewLogEntry(string $entryId, string $systemId, string $message, string $actionType, string $objectType) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('external_system_log', ['entryId', 'systemId', 'message', 'actionType', 'objectType'])
            ->values([$entryId, $systemId, $message, $actionType, $objectType])
            ->execute();

        return $qb->fetchBool();
    }
}

?>