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

    /**
     * Inserts a new container database
     * 
     * @param string $entryId Entry ID
     * @param string $containerId Container ID
     * @param string $databaseName Database name
     * @param string $title Database title
     * @param string $description Database description
     * @param bool $isDefault Is the database default
     */
    public function insertNewContainerDatabase(string $entryId, string $containerId, string $databaseName, string $title, string $description, bool $isDefault = false): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('container_databases', ['entryId', 'containerId', 'name', 'isDefault', 'title', 'description'])
            ->values([$entryId, $containerId, $databaseName, $isDefault, $title, $description])
            ->execute();

        return $qb->fetchBool();
    }
}

?>