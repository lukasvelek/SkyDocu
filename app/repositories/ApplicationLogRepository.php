<?php

namespace App\Repositories;

use QueryBuilder\QueryBuilder;

/**
 * ApplicationLogRepository contains low-level API methods
 * 
 * @author Lukas Velek
 */
class ApplicationLogRepository extends ARepository {
    /**
     * Inserts new data
     * 
     * @param array $data Data array
     */
    public function insertNewData(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('application_log', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Composes query for application log and returns an instance of QueryBuilder
     */
    public function composeQueryForApplicationLog(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('application_log');

        return $qb;
    }
}