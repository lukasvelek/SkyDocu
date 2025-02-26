<?php

namespace App\Managers;

use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseManager;
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

        $col = 'Tables_' . $database->getName();

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
     * @param string $entryId Entry ID
     */
    public function dropDatabaseByEntryId(string $entryId) {
        $database = $this->getDatabaseByEntryId($entryId);

        $this->dbManager->dropDatabase($database->getName());

        if(!$this->containerDatabaseRepository->deleteContainerDatabase($entryId)) {
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
}

?>