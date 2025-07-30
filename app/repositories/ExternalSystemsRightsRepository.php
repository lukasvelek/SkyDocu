<?php

namespace App\Repositories;

use QueryBuilder\QueryBuilder;

/**
 * ExternalSystemsRightsRepository contains low-level API methods
 * 
 * @author Lukas Velek
 */
class ExternalSystemsRightsRepository extends ARepository {
    /**
     * Composes a QueryBuilder instance for external system rights
     */
    public function composeQueryForExternalSystemRights(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_system_rights');

        return $qb;
    }

    /**
     * Inserts a new operation right
     * 
     * @param array $data Data array
     */
    public function insertOperationRight(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('external_system_rights', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Updates operation right
     * 
     * @param string $systemId System ID
     * @param string $operationName Operation name
     * @param array $data Data array
     */
    public function updateOperationRight(string $systemId, string $operationName, array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->update('external_system_rights')
            ->set($data)
            ->where('systemId = ?', [$systemId])
            ->andWhere('operationName = ?', [$operationName])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Deletes operation rights for given system
     * 
     * @param string $systemId System ID
     */
    public function deleteOperationRightsForSystem(string $systemId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('external_system_rights')
            ->where('systemId = ?', [$systemId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Delets operation rights for given container
     * 
     * @param string $containerId Container ID
     */
    public function deleteOperationRightsForContainer(string $containerId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('external_system_rights')
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetchBool();
    }
}