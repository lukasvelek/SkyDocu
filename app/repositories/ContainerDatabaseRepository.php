<?php

namespace App\Repositories;

use QueryBuilder\QueryBuilder;

/**
 * ContainerDatabaseRepository contains low-level database operations
 * 
 * @author Lukas Velek
 */
class ContainerDatabaseRepository extends ARepository {
    /**
     * Composes a QueryBuilder instance for container_databases table
     */
    public function composeQueryForContainerDatabases(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_databases');

        return $qb;
    }
}

?>