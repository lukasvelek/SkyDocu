<?php

namespace App\Core\DB;

use App\Core\DatabaseConnection;
use App\Core\FileManager;
use App\Exceptions\AException;
use App\Exceptions\DatabaseExecutionException;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\Logger\Logger;
use Exception;
use mysqli_sql_exception;

/**
 * DatabaseMigrationManager helps with running database migrations
 * 
 * @author Lukas Velek
 */
class DatabaseMigrationManager {
    private const SYSTEM_MIGRATION_FILE_PATH = APP_ABSOLUTE_DIR . 'app\\core\\';
    private const SYSTEM_MIGRATION_FILE_NAME = 'migration';

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
     * 
     * @param bool $throwExceptions True if exceptions should be thrown
     * @return int Database schema
     */
    public function runMigrations(bool $throwExceptions = false) {
        // get migrations
        $this->logger->info('Starting migrations...', __METHOD__);

        $migrations = $this->getAvailableMigrations();
        $this->logger->info(sprintf('Found total of %d migrations.', count($migrations)), __METHOD__);
        
        $this->filterOnlyUpstreamMigrations($migrations);
        $this->logger->info(sprintf('Found %d migrations that must be run.', count($migrations)), __METHOD__);

        $dbSchema = 0;
        foreach($migrations as $migration) {
            $dbSchema = $this->runSingleMigration($migration, $throwExceptions);
        }

        return $dbSchema;
    }

    /**
     * Returns all available migrations
     */
    public function getAvailableMigrations() {
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
    public function filterOnlyUpstreamMigrations(array &$migrations) {
        $lastMigration = $this->getLastRunMigration();

        $this->logger->info('Last migration found: ' . $lastMigration, __METHOD__);

        if($lastMigration !== null) {
            $skip = true;
            $filteredMigrations = [];
            foreach($migrations as $migration) {
                $className = FileManager::getFilenameFromPath($migration);

                $migrationNameParts = explode('_', $className);
                $migrationNumber = $migrationNameParts[count($migrationNameParts) - 2];

                if(!$skip) {
                    $filteredMigrations[] = $migration;
                }

                if($this->containerId === null) {
                    if($className == $lastMigration) {
                        $skip = false;
                    }
                } else {
                    $this->logger->info('Current database schema for given container: ' . (int)$migrationNumber, __METHOD__);
                    
                    if((int)$migrationNumber == (int)$lastMigration) {
                        $skip = false;
                    }
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
            $sql = 'SELECT dbSchema FROM container_databases WHERE containerId = \'' . $this->containerId . '\' AND isDefault = 1';

            $dbResult = $this->query($sql, __METHOD__);

            if($dbResult !== false) {
                foreach($dbResult as $row) {
                    if($row['dbSchema'] > 0) {
                        $result = $row['dbSchema'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Runs a single migration
     * 
     * @param string $migrationFilePath File path of the migration
     * @param bool $throwExceptions True if exceptions should be thrown
     * @return int Migration number
     */
    private function runSingleMigration(string $migrationFilePath, bool $throwExceptions) {
        require_once($migrationFilePath);

        $fileName = FileManager::getFilenameFromPath($migrationFilePath);
        $className = str_replace('-', '_', $fileName);
        
        $this->logger->info(sprintf('Running migration \'%s\' located in (\'%s\').', $className, $migrationFilePath), __METHOD__);

        $migrationNameParts = explode('_', $fileName);
        $migrationName = $migrationNameParts[count($migrationNameParts) - 1];
        $migrationNumber = $migrationNameParts[count($migrationNameParts) - 2];

        try {
            $fullClassName = '\\App\\Data\\Db\\Migrations\\' . ($this->containerId !== null ? 'Containers\\' : '') . $className;

            /**
             * @var ABaseMigration $object
             */
            $object = new $fullClassName($fileName, $migrationName, $migrationNumber);
            
            if($this->containerId !== null) {
                $object->inject($this->conn, $this->masterConn);
            } else {
                $object->inject($this->masterConn, $this->masterConn);
            }

            $tableSchema = $object->up();
            $tables = $tableSchema->getTableSchemas();

            foreach($tables as $name => $table) {
                /** @var \App\Core\DB\Helpers\Schema\ABaseTableSchema $table */
                
                $sqls = $table->getSQL();
                
                try {
                    $this->masterConn->beginTransaction();

                    foreach($sqls as $sql) {
                        $this->query($sql, __METHOD__, ($this->containerId === null));
                    }

                    if($this->containerId !== null) {
                        $this->query($this->getContainerUpdateDbSchemaSQL((int)$migrationNumber), __METHOD__);
                    } else {
                        if(!$this->saveSystemLastMigration($fileName)) {
                            throw new GeneralException('Could not save last migration run for system.');
                        }
                    }

                    $this->masterConn->commit();
                } catch(Exception|mysqli_sql_exception $e) {
                    $this->masterConn->rollback();
                    $this->logger->error('An error occurred during running migration. Exception: ' . $e->getMessage(), __METHOD__);

                    if($throwExceptions) {
                        throw new GeneralException('Database error.', $e);
                    }
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
                        $this->query($sql, __METHOD__, ($this->containerId === null));
                    }

                    $this->masterConn->commit();
                } catch(Exception $e) {
                    $this->masterConn->rollback();
                    $this->logger->error('An error occurred during running seeding for table \'' . $tableName . '\'. Exception: ' . $e->getMessage(), __METHOD__);

                    if($throwExceptions) {
                        throw $e;
                    }
                }
            }
        } catch(AException $e) {
            throw $e;
        }

        return (int)$migrationNumber;
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

    /**
     * Performs an SQL query and saves it to the log file
     * 
     * @param string $sql SQL query
     * @param string $method Calling method
     * @param bool $useMasterConn Use master database connection or container database connection
     * @return mixed Database result
     */
    private function query(string $sql, string $method, bool $useMasterConn = true): mixed {
        $tsStart = null;
        $tsEnd = null;

        $q = function(string $sql) use ($useMasterConn, &$tsStart, &$tsEnd) {
            $tsStart = hrtime(true);
            try {
                if($useMasterConn) {
                    $result = $this->masterConn->query($sql);
                } else {
                    $result = $this->conn->query($sql);
                }
            } catch(\mysqli_sql_exception $e) {
                throw new DatabaseExecutionException($e, $sql, $e);
            }
            $tsEnd = hrtime(true);
            return $result;
        };

        $result = $q($sql);

        $diff = $tsEnd - $tsStart;

        $diff = DateTimeFormatHelper::convertNsToMs($diff);

        $e = new Exception;

        $this->logger->sql($sql, $method, $diff, $e);

        return $result;
    }

    /**
     * Returns the release date of the migration's database schema - version
     * 
     * @param int $dbSchema Migration's database schema
     * @param bool $isContainer Is container?
     */
    public static function getMigrationReleaseDateFromNumber(int $dbSchema, bool $isContainer): ?string {
        $path = APP_ABSOLUTE_DIR . 'data\\db\\migrations';

        if($isContainer) {
            $path .= '\\containers';
        }

        $files = FileManager::getFilesInFolder($path);

        $releaseDate = null;
        foreach($files as $filename => $fileFullPath) {
            $fileparts = explode('_', $filename);

            if($dbSchema == (string)$fileparts[2]) {
                $releaseDate = $fileparts[1];
            }
        }

        return $releaseDate;
    }
}

?>