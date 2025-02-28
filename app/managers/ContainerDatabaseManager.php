<?php

namespace App\Managers;

use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseManager;
use App\Core\DB\DatabaseRow;
use App\Entities\ContainerDatabaseEntity;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\ContainerDatabaseRepository;

/**
 * ContainerDatabaseManager is used for managing container databases
 * 
 * @author Lukas Velek
 */
class ContainerDatabaseManager extends AManager {
    private ContainerDatabaseRepository $containerDatabaseRepository;
    private DatabaseManager $dbManager;

    /**
     * Class constructor
     * 
     * @param Logger $logger
     * @param EntityManager $entityManager
     * @param ContainerDatabaseRepository $containerDatabaseRepository
     * @param DatabaseManager $dbManager
     */
    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        ContainerDatabaseRepository $containerDatabaseRepository,
        DatabaseManager $dbManager
    ) {
        parent::__construct($logger, $entityManager);

        $this->containerDatabaseRepository = $containerDatabaseRepository;
        $this->dbManager = $dbManager;
    }

    /**
     * Truncates database
     * 
     * @param string $entryId Entry ID
     */
    public function truncateDatabaseByEntryId(string $entryId) {
        $database = $this->getDatabaseByEntryId($entryId);

        $tablesQ = $this->dbManager->getAllTablesInDatabase($database->getName());

        $col = 'Tables_in_' . $database->getName();

        $_tables = [];
        foreach($tablesQ as $row) {
            $table = $row[$col];
            $_tables[] = $table;
        }

        if(empty($_tables)) {
            throw new GeneralException('No tables found in selected database. Therefore truncation could not be completed.');
        }

        foreach($_tables as $table) {
            $this->dbManager->truncateTableInDatabase($database->getName(), $table);
        }
    }

    /**
     * Returns an instance of ContainerDatabaseEntity for given entry ID
     * 
     * @param string $entryId Entry ID
     */
    public function getDatabaseByEntryId(string $entryId): ContainerDatabaseEntity {
        $entry = $this->containerDatabaseRepository->getDatabaseByEntryId($entryId);

        if($entry === null) {
            throw new NonExistingEntityException('Database does not exist.');
        }

        return ContainerDatabaseEntity::createEntityFromDbRow($entry);
    }

    /**
     * Drops database
     * 
     * @param string $containerId Container ID
     * @param string $entryId Entry ID
     */
    public function dropDatabaseByEntryId(string $containerId, string $entryId) {
        $database = $this->getDatabaseByEntryId($entryId);

        $this->dbManager->dropDatabase($database->getName());

        if(!$this->containerDatabaseRepository->deleteContainerDatabase($entryId)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->containerDatabaseRepository->deleteContainerDatabaseTables($containerId, $entryId)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->containerDatabaseRepository->deleteColumnsForAllContainerDatabaseTables($containerId, $entryId)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::CONTAINER_DATABASES)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    /**
     * Inserts a new container database
     * 
     * @param string $containerId Container ID
     * @param string $databaseName Database name
     * @param string $title Database title
     * @param string $description Database description
     * @param bool $isDefault Is the database default
     */
    public function insertNewContainerDatabase(string $containerId, string $databaseName, string $title, string $description, bool $isDefault = false) {
        $entryId = $this->createId(EntityManager::CONTAINER_DATABASES);

        if(!$this->containerDatabaseRepository->insertNewContainerDatabase($entryId, $containerId, $databaseName, $title, $description, $isDefault)) {
            throw new GeneralException('Database error.');
        }

        $this->dbManager->createNewDatabase($databaseName);

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::CONTAINER_DATABASES)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    /**
     * Inserts a new container database table
     * 
     * @param string $containerId Container ID
     * @param string $databaseId Database ID
     * @param string $name Table name
     */
    public function insertNewContainerDatabaseTable(string $containerId, string $databaseId, string $name) {
        $entryId = $this->createId(EntityManager::CONTAINER_DATABASE_TABLES);

        if(!$this->containerDatabaseRepository->insertNewContainerDatabaseTable($entryId, $containerId, $databaseId, $name)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Updates given container database table
     * 
     * @param string $tableId Table ID
     * @param array $data Data
     */
    public function updateContainerDatabaseTable(string $tableId, array $data) {
        if(!$this->containerDatabaseRepository->updateContainerDatabaseTable($tableId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Inserts a new container database table column
     * 
     * @param string $containerId Container ID
     * @param string $databaseId Database ID
     * @param string $tableId Table ID
     * @param string $name Column name
     * @param string $title Column title
     * @param string $definition Column definition
     */
    public function insertNewContainerDatabaseTableColumn(string $containerId, string $databaseId, string $tableId, string $name, string $title, string $definition) {
        $entryId = $this->createId(EntityManager::CONTAINER_DATABASE_TABLE_COLUMNS);

        if(!$this->containerDatabaseRepository->insertNewContainerDatabaseTableColumnDefinition($entryId, $containerId, $databaseId, $tableId, $name, $title, $definition)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Checks if given database table can be created
     * 
     * @param string $containerId Container ID
     * @param string $databaseId Database ID
     * @param string $tableId Table ID
     */
    public function canContainerDatabaseTableBeCreated(string $containerId, string $databaseId, string $tableId): bool {
        $qb = $this->containerDatabaseRepository->composeQueryForContainerDatabaseTableColumns();
        $qb->select(['COUNT(*) AS cnt'])
            ->andWhere('containerId = ?', [$containerId])
            ->andWhere('databaseId = ?', [$databaseId])
            ->andWhere('tableId = ?', [$tableId])
            ->execute();

        $cnt = $qb->fetch('cnt');

        $table = $this->getContainerDatabaseTableById($containerId, $databaseId, $tableId);

        return ($cnt > 0) && !$table->isCreated;
    }

    /**
     * Returns an instance of DatabaseRow for given container database table
     * 
     * @param string $containerId Container ID
     * @param string $databaseId Database ID
     * @param string $name Table name
     */
    public function getContainerDatabaseTableByName(string $containerId, string $databaseId, string $name): DatabaseRow {
        $qb = $this->containerDatabaseRepository->composeQueryForContainerDatabaseTables();
        $qb->andWhere('containerId = ?', [$containerId])
            ->andWhere('databaseId = ?', [$databaseId])
            ->andWhere('name = ?', [$name])
            ->execute();

        return DatabaseRow::createFromDbRow($qb->fetch());
    }

    /**
     * Returns an instance of DatabaseRow for given container database table
     * 
     * @param string $containerId Container ID
     * @param string $databaseId Database ID
     * @param string $tableId Table ID
     */
    public function getContainerDatabaseTableById(string $containerId, string $databaseId, string $tableId): DatabaseRow {
        $qb = $this->containerDatabaseRepository->composeQueryForContainerDatabaseTables();
        $qb->andWhere('containerId = ?', [$containerId])
            ->andWhere('databaseId = ?', [$databaseId])
            ->andWhere('entryId = ?', [$tableId])
            ->execute();

        return DatabaseRow::createFromDbRow($qb->fetch());
    }
}

?>