<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class ExternalSystemRightsRepository extends ARepository {
    public function composeQueryForExternalSystemRights() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_system_rights');

        return $qb;
    }

    public function insertAllowedExternalSystemOperation(string $rightId, string $systemId, string $operationName, bool $isEnabled = true) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('external_system_rights', ['rightId', 'systemId', 'operationName', 'isEnabled'])
            ->values([$rightId, $systemId, $operationName, $isEnabled])
            ->execute();

        return $qb->fetchBool();
    }

    public function deleteAllExternalSystemOperations(string $systemId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('external_system_rights')
            ->where('systemId = ?', [$systemId])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateExternalSystemOperation(string $systemId, string $operationName, bool $enable) {
        $qb = $this->qb(__METHOD__);

        $qb->update('external_system_rights')
            ->set([
                'isEnabled' => $enable
            ])
            ->where('systemId = ?', [$systemId])
            ->andWhere('operationName = ?', [$operationName])
            ->execute();

        return $qb->fetchBool();
    }
}

?>