<?php

namespace App\Repositories;

use QueryBuilder\QueryBuilder;

/**
 * Application log repository contains low-level API methods and other useful methods
 * for working with application log and database transactions
 * 
 * @author Lukas Velek
 */
class ApplicationLogRepository extends ARepository {
    /**
     * Composes a QueryBuilder instance for application_log
     */
    public function composeQueryForApplicationLog(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('application_log');

        return $qb;
    }

    /**
     * Inserts a new entry to the database
     * 
     * @param array $data Data array
     */
    public function insertNewEntry(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('application_log', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }
}