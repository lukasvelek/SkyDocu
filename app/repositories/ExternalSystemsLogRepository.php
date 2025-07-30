<?php

namespace App\Repositories;

use QueryBuilder\QueryBuilder;

/**
 * ExternalSystemsLogRepository contains low-level API methods
 * 
 * @author Lukas Velek
 */
class ExternalSystemsLogRepository extends ARepository {
    /**
     * Inserts a new log entry
     * 
     * @param array $data Data array
     */
    public function insertNewLogEntry(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('external_system_log', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Composes a QueryBuilder instance for log entries
     */
    public function composeQueryForLogEntries(): QueryBuilder {
        $qb = $this->qb(__METHOD__);
        
        $qb->select(['*'])
            ->from('external_system_log');
        
        return $qb;
    }

    /**
     * Composes a QueryBuilder instance for log entries for given system
     * 
     * @param string $systemId System ID
     */
    public function composeQueryForLogEntriesForSystem(string $systemId): QueryBuilder {
        $qb = $this->composeQueryForLogEntries();
        
        $qb->andWhere('systemId = ?', [$systemId]);

        return $qb;
    }

    /**
     * Composes a QueryBuilder instance for log entries for given container
     * 
     * @param string $container Container ID
     */
    public function composeQueryForLogEntriesForContainer(string $containerId): QueryBuilder {
        $qb = $this->composeQueryForLogEntries();

        $qb->andWhere('containerId = ?', [$containerId]);

        return $qb;
    }

    /**
     * Deletes log entries for given system
     * 
     * @param string $systemId System ID
     */
    public function deleteLogEntriesForSystem(string $systemId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('external_system_log')
            ->where('systemId = ?', [$systemId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Deletes log entries for given container
     * 
     * @param string $containerId Container ID
     */
    public function deleteLogEntriesForContainer(string $containerId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('external_system_log')
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetchBool();
    }
}