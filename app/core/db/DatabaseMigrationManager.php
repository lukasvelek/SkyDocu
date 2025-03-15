<?php

namespace App\Core\DB;

use App\Core\DatabaseConnection;
use App\Core\FileManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use Exception;

/**
 * DatabaseMigrationManager helps with running database migrations
 * 
 * @author Lukas Velek
 */
class DatabaseMigrationManager {
    private const SYSTEM_MIGRATION_FILE_PATH = APP_ABSOLUTE_DIR . 'app\\core\\';
    private const SYSTEM_MIGRATION_FILE_NAME = 'migration';

    private bool $log = true;

    private DatabaseConnection $masterConn;
    private ?DatabaseConnection $conn;
    private Logger $logger;

    private ?string $containerId = null;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $masterConn Master DatabaseConnection instance
     * @param DatabaseConnection $conn DatabaseConnection instance
     * @param Logger $logger Logger instance
     */
    public function __construct(DatabaseConnection $masterConn, ?DatabaseConnection $conn, Logger $logger) {
        $this->masterConn = $masterConn;
        $this->conn = $conn;
        $this->logger = $logger;
    }

    /**
     * Sets the container ID
     * 
     * @param string $containerId Container ID
     */
    public function setContainer(string $containerId) {
        $this->containerId = $containerId;
    }

    /**
     * Runs all migrations
     */
    public function runMigrations() {
        // get migrations
        $this->logger->info('Starting migrations...', __METHOD__);

        $migrations = $this->getAvailableMigrations();
        $this->logger->info(sprintf('Found total of %d migrations.', count($migrations)), __METHOD__);
        
        $this->filterOnlyUpstreamMigrations($migrations);
        $this->logger->info(sprintf('Found %d migrations that must be run.', count($migrations)), __METHOD__);

        foreach($migrations as $migration) {
            $this->runSingleMigration($migration);
        }
    }

    /**
     * Returns all available migrations
     */
    private function getAvailableMigrations() {
        $migrationDir = APP_ABSOLUTE_DIR . 'data\\db\\migrations';

        if($this->containerId !== null) {
            $migrationDir .= '\\containers';
        }

        $migrations = FileManager::getFilesInFolder($migrationDir);

        return $migrations;
    }

    /**
     * Filters between available migrations and returns only the necessary ones
     * 
     * @param array &$migrations Migrations
     */
    private function filterOnlyUpstreamMigrations(array &$migrations) {
        $lastMigration = $this->getLastRunMigration();

        if($lastMigration !== null) {
            $skip = true;
            $filteredMigrations = [];
            foreach($migrations as $migration) {
                $className = FileManager::getFilenameFromPath($migration);

                $migrationNameParts = explode('_', $className);
                $migrationNumber = $migrationNameParts[count($migrationNameParts) - 2];

                if($this->containerId === null) {
                    if($className == $lastMigration) {
                        $skip = false;
                    }
                } else {
                    if((int)$migrationNumber == (int)$lastMigration) {
                        $skip = false;
                    }
                }

                if(!$skip) {
                    $filteredMigrations[] = $migration;
                }
            }

            $migrations = $filteredMigrations;
        }
    }

    /**
     * Gets last migration run
     */
    private function getLastRunMigration(): ?string {
        $result = null;

        if($this->containerId === null) {
            // FILE STRUCTURE: migration_2025-03-15_0001_initial

            $path = self::SYSTEM_MIGRATION_FILE_PATH . self::SYSTEM_MIGRATION_FILE_NAME;

            if(FileManager::fileExists($path)) {
                $result = FileManager::loadFile($path);
            }
        } else {
            $dbResult = $this->masterConn->query('SELECT dbSchema FROM container_databases WHERE containerId = \'' . $this->containerId . '\' AND isDefault = 1');

            if($dbResult !== false) {
                foreach($dbResult as $row) {
                    $result = $row['dbSchema'];
                }
            }
        }

        return $result;
    }

    /**
     * Runs a single migration
     * 
     * @param string $migrationFilePath File path of the migration
     */
    private function runSingleMigration(string $migrationFilePath) {
        require_once($migrationFilePath);

        $fileName = FileManager::getFilenameFromPath($migrationFilePath);
        $className = str_replace('-', '_', $fileName);
        
        $this->logger->info(sprintf('Running migration \'%s\' located in (\'%s\').', $className, $migrationFilePath), __METHOD__);

        $migrationNameParts = explode('_', $fileName);
        $migrationName = $migrationNameParts[count($migrationNameParts) - 1];
        $migrationNumber = $migrationNameParts[count($migrationNameParts) - 2];

        try {
            $fullClassName = '\\App\\Data\\Db\\Migrations\\' . $className;

            /**
             * @var ABaseMigration $object
             */
            $object = new $fullClassName($fileName, $migrationName, $migrationNumber);
            
            if($this->containerId !== null) {
                $object->inject($this->conn);
            } else {
                $object->inject($this->masterConn);
            }

            $tableSchema = $object->up();
            $tables = $tableSchema->getTableSchemas();

            foreach($tables as $name => $table) {
                /** @var \App\Core\DB\Helpers\Schema\ABaseTableSchema $table */
                
                $sqls = $table->getSQL();
                
                try {
                    $this->masterConn->beginTransaction();

                    foreach($sqls as $sql) {
                        if($this->containerId === null) {
                            $this->masterConn->query($sql);
                        } else {
                            $this->conn->query($sql);
                        }
                    }

                    if($this->containerId !== null) {
                        $this->masterConn->query($this->getContainerUpdateDbSchemaSQL((int)$migrationNumber));
                    } else {
                        if(!$this->saveSystemLastMigration($fileName)) {
                            throw new GeneralException('Could not save last migration run for system.');
                        }
                    }

                    $this->masterConn->commit();
                } catch(Exception $e) {
                    $this->masterConn->rollback();
                    $this->logger->error('An error occurred during running migration. Exception: ' . $e->getMessage(), __METHOD__);
                }
            }

            $tableSeeds = $object->seeding();
            $seeds = $tableSeeds->getSeeds();

            foreach($seeds as $tableName => $seed) {
                /** @var \App\Core\DB\Helpers\Seeding\CreateTableSeeding $seed */

                $sqls = $seed->getSQL();

                try {
                    $this->masterConn->beginTransaction();

                    foreach($sqls as $sql) {
                        if($this->log) {
                            $this->logger->info('SQL: ' . $sql, __METHOD__);
                        }

                        if($this->containerId === null) {
                            $this->masterConn->query($sql);
                        } else {
                            $this->conn->query($sql);
                        }
                    }

                    $this->masterConn->commit();
                } catch(Exception $e) {
                    $this->masterConn->rollback();
                    $this->logger->error('An error occurred during running seeding for table \'' . $tableName . '\'. Exception: ' . $e->getMessage(), __METHOD__);
                }
            }
        } catch(AException $e) {
            throw $e;
        }
    }

    /**
     * Returns SQL query for container db schema update
     * 
     * @param int $migrationNumber Migration number
     */
    private function getContainerUpdateDbSchemaSQL(int $migrationNumber): mixed {
        return 'UPDATE container_databases SET dbSchema = ' . $migrationNumber . ' WHERE containerId = \'' . $this->containerId . '\' AND isDefault = 1';
    }

    /**
     * Saves last migration run for system
     * 
     * @param string $migration Migration name
     */
    private function saveSystemLastMigration(string $migration): bool {
        return FileManager::saveFile(self::SYSTEM_MIGRATION_FILE_PATH, self::SYSTEM_MIGRATION_FILE_NAME, $migration, true) !== false;
    }
}

?>