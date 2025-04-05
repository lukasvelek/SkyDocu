<?php

namespace App\Core\DB;

use App\Core\DatabaseConnection;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;
use App\Core\HashManager;
use App\Logger\Logger;
use App\Managers\EntityManager;
use QueryBuilder\QueryBuilder;

/**
 * Common class for all database migrations
 * 
 * @author Lukas Velek
 */
abstract class ABaseMigration {
    private string $migrationName;
    private string $migrationFullname;
    private string $migrationNumber;
    private ?TableSchema $tableSchema = null;
    private DatabaseConnection $conn;
    private Logger $logger;

    protected DatabaseConnection $masterConn;

    /**
     * Class constructor
     * 
     * @param string $migrationFullname Fullname of the migration
     * @param string $migrationName Name of the migration
     * @param string $migrationNumber Number of the migration
     */
    public function __construct(string $migrationFullname, string $migrationName, string $migrationNumber) {
        $this->migrationFullname = $migrationFullname;
        $this->migrationName = $migrationName;
        $this->migrationNumber = $migrationNumber;
    }

    /**
     * Injects DatabaseConnection instance
     * 
     * @param DatabaseConnection $conn DatabaseConnection instance
     * @param DatabaseConnection $masterConn Master DatabaseConnection instance
     * @param Logger $logger Logger instance
     */
    public function inject(DatabaseConnection $conn, DatabaseConnection $masterConn, Logger $logger) {
        $this->conn = $conn;
        $this->masterConn = $masterConn;
        $this->logger = $logger;
    }

    /**
     * Defines what happens when the migration is incrementally run
     */
    public abstract function up(): TableSchema;

    /**
     * Defines what happens when the migration is decrementally run
     */
    public abstract function down(): TableSchema;

    /**
     * Defines data seeding
     */
    public abstract function seeding(): TableSeeding;

    /**
     * Returns a new instance of TableSchema class
     */
    protected function getTableSchema(): TableSchema {
        if($this->tableSchema === null) {
            $this->tableSchema = new TableSchema();
        }

        return $this->tableSchema;
    }

    /**
     * Returns a new instance of TableSeeding class
     */
    protected function getTableSeeding(): TableSeeding {
        return new TableSeeding();
    }

    /**
     * Generates unique ID
     * 
     * @param string $tableName Table name
     * @param ?string $primaryKeyName Primary key name or null for auto-complete
     */
    protected function getId(string $tableName, ?string $primaryKeyName = null): ?string {
        if($primaryKeyName === null) {
            $primaryKeyName = EntityManager::getPrimaryKeyNameByCategory($tableName);
        }

        $runs = 0;
        $maxRuns = 1000;

        $final = null;
        while($runs < $maxRuns) {
            $id = HashManager::createEntityId();

            $result = $this->conn->query('SELECT COUNT(' . $primaryKeyName . ') AS cnt FROM ' . $tableName . ' WHERE ' . $primaryKeyName . ' = \'' . $id . '\'');

            if($result !== false) {
                foreach($result as $row) {
                    if($row['cnt'] == 0) {
                        $final = $id;
                        break;
                    }
                }
            }

            $runs++;
        }

        return $final;
    }

    protected function getValueFromTableByConditions(string $tableName, string $columnName, string $conditionColumnName, string|int|bool $conditionColumnValue) {
        $qb = new QueryBuilder($this->conn, $this->logger, __METHOD__);

        $qb->select([$columnName])
            ->from($tableName)
            ->where($conditionColumnName . ' = ?', [$conditionColumnValue])
            ->execute();

        return $qb->fetch($columnName);
    }
}

?>