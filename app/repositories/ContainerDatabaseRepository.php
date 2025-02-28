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

    /**
     * Returns a database entry by entry ID
     * 
     * @param string $entryId Entry ID
     */
    public function getDatabaseByEntryId(string $entryId): mixed {
        $qb = $this->composeQueryForContainerDatabases();

        $qb->andWhere('entryId = ?', [$entryId])
            ->execute();

        return $qb->fetch();
    }

    /**
     * Deletes a container database
     * 
     * @param string $entryId Entry ID
     */
    public function deleteContainerDatabase(string $entryId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('container_databases')
            ->where('entryId = ?', [$entryId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Deletes container database tables
     * 
     * @param string $containerId Container ID
     * @param string $databaseId Database ID
     */
    public function deleteContainerDatabaseTables(string $containerId, string $databaseId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('container_database_tables')
            ->where('containerId = ?', [$containerId])
            ->andWhere('databaseId = ?', [$databaseId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Inserts a new container database table
     * 
     * @param string $entryId Entry ID
     * @param string $containerId Container ID
     * @param string $databaseId Database ID
     * @param string $name Table name
     */
    public function insertNewContainerDatabaseTable(string $entryId, string $containerId, string $databaseId, string $name): bool {
        $qb = $this->qb(__METHOD__);
        
        $qb->insert('container_database_tables', ['entryId', 'containerId', 'databaseId', 'name'])
            ->values([$entryId, $containerId, $databaseId, $name])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Updates container database table
     * 
     * @param string $entryId Entry ID
     * @param array $data Data
     */
    public function updateContainerDatabaseTable(string $entryId, array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->update('container_database_tables')
            ->set($data)
            ->where('entryId = ?', [$entryId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Composes a QueryBuilder instance for container_database_tables
     */
    public function composeQueryForContainerDatabaseTables(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_database_tables');

        return $qb;
    }

    /**
     * Deletes columns for all container database tables
     * 
     * @param string $containerId Container ID
     * @param string $databaseId Database ID
     */
    public function deleteColumnsForAllContainerDatabaseTables(string $containerId, string $databaseId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('container_database_table_columns')
            ->where('containerId = ?', [$containerId])
            ->andWhere('databaseId = ?', [$databaseId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Inserts a new container database table column definition
     * 
     * @param string $entryId Entry ID
     * @param string $containerId Container ID
     * @param string $databaseId Database ID
     * @param string $tableId Table ID
     * @param string $name Column name
     * @param string $title Column title
     * @param string $definition Column definition
     */
    public function insertNewContainerDatabaseTableColumnDefinition(string $entryId, string $containerId, string $databaseId, string $tableId, string $name, string $title, string $definition): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('container_database_table_columns', [
            'entryId',
            'containerId',
            'databaseId',
            'tableId',
            'name',
            'title',
            'definition'
            ])
            ->values([
                $entryId,
                $containerId,
                $databaseId,
                $tableId,
                $name,
                $title,
                $definition
            ])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Composes a QueryBuilder instance for container_database_table_columns
     */
    public function composeQueryForContainerDatabaseTableColumns(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_database_table_columns');

        return $qb;
    }
}

?>