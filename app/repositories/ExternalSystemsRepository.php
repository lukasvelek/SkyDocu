<?php

namespace App\Repositories;

use QueryBuilder\QueryBuilder;

/**
 * ExternalSystemsRepository contains low-level API methods for external systems
 * 
 * @author Lukas Velek
 */
class ExternalSystemsRepository extends ARepository {
    /**
     * Composes a QueryBuilder instance for external systems
     */
    public function composeQueryForExternalSystems(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_systems');

        return $qb;
    }

    /**
     * Composes a QueryBuilder instance for external systems in given container
     * 
     * @param string $containerId Container ID
     */
    public function composeQueryForExternalSystemsForContainer(string $containerId): QueryBuilder {
        $qb = $this->composeQueryForExternalSystems();

        $qb->andWhere('containerId = ?', [$containerId]);

        return $qb;
    }

    /**
     * Returns external system by ID
     * 
     * @param string $systemId System ID
     */
    public function getExternalSystemById(string $systemId): mixed {
        $qb = $this->composeQueryForExternalSystems();

        $qb->andWhere('systemId = ?', [$systemId])
            ->execute();

        return $qb->fetch();
    }

    /**
     * Creates a new external system
     * 
     * @param array $data Data array
     */
    public function createNewExternalSystem(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('external_systems', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Updates external system
     * 
     * @param string $systemId System ID
     * @param array $data Data array
     */
    public function updateExternalSystem(string $systemId, array $data): bool {
        $qb = $this->qb(__METHOD__);
        
        $qb->update('external_systems')
            ->set($data)
            ->where('systemId = ?', [$systemId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Updates external systems in bulk
     * 
     * @param array $systemIds System IDs
     * @param array $data Data array
     */
    public function bulkUpdateExternalSystems(array $systemIds, array $data): bool {
        $qb = $this->qb(__METHOD__);
        
        $qb->update('external_systems')
            ->set($data)
            ->where($qb->getColumnInValues('systemId', $systemIds))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Deletes given external system
     * 
     * @param string $systemId System ID
     */
    public function deleteExternalSystem(string $systemId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('external_systems')
            ->where('systemId = ?', [$systemId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Deletes external systems for given container
     * 
     * @param string $containerId Container ID
     */
    public function deleteExternalSystemsForContainer(string $containerId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('external_systems')
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Returns external system by login
     * 
     * @param string $login Login
     */
    public function getExternalSystemByLogin(string $login) {
        $qb = $this->composeQueryForExternalSystems();

        $qb->andWhere('login = ?', [$login])
            ->execute();

        return $qb->fetch();
    }
}