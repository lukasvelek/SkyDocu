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
    protected DatabaseConnection $conn;
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

    /**
     * Generates unique hash
     * 
     * @param int $length Hash length
     * @param string $tableName Table name
     * @param string $column Column name
     */
    protected function getUniqueHash(int $length, string $tableName, string $column): ?string {
        $runs = 0;
        $maxRuns = 1000;

        $final = null;
        while($runs < $maxRuns) {
            $hash = HashManager::createHash($length, false);

            $result = $this->conn->query('SELECT COUNT(' . $column . ') AS cnt FROM ' . $tableName . ' WHERE ' . $column . ' = \'' . $hash . '\'');

            if($result !== false) {
                foreach($result as $row) {
                    if($row['cnt'] == 0) {
                        $final = $hash;
                        break;
                    }
                }
            }

            $runs++;
        }

        return $final;
    }

    /**
     * Returns a value from given table by single condition
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param string $conditionColumnName Condition column name
     * @param string|int|bool $conditionColumnValue Condition column value
     */
    protected function getValueFromTableByConditions(string $tableName, string $columnName, string $conditionColumnName, string|int|bool $conditionColumnValue): mixed {
        $qb = $this->qb(__METHOD__);

        $qb->select([$columnName])
            ->from($tableName)
            ->where($conditionColumnName . ' = ?', [$conditionColumnValue])
            ->execute();

        return $qb->fetch($columnName);
    }

    /**
     * Returns a unique process ID for process title
     * 
     * @param string $processTitle Process title
     */
    protected function getUniqueProcessIdForProcessTitle(string $processTitle): mixed {
        $qb = $this->qb(__METHOD__);

        $qb->select(['uniqueProcessId'])
            ->from('processes')
            ->where('title = ?', [$processTitle])
            ->andWhere('status = 1')
            ->orderBy('dateCreated', 'DESC')
            ->limit(1)
            ->execute();

        $result = $qb->fetch('uniqueProcessId');

        return $result;
    }

    /**
     * Returns technical user's ID or null if no technical user exists
     */
    protected function getTechnicalUserId(): ?string {
        $sql = 'SELECT userId FROM users WHERE username = "service_user"';

        $result = $this->masterConn->query($sql);

        $userId = null;
        foreach($result as $row) {
            $userId = $row['userId'];
        }

        return $userId;
    }

    /**
     * Checks if there is a column with given value in given table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param mixed $columnValue Column value
     */
    protected function checkValueExistsInTable(string $tableName, string $columnName, mixed $columnValue): bool {
        $qb = $this->qb(__METHOD__);

        $qb->select(['COUNT(*) AS cnt'])
            ->from($tableName)
            ->where($columnName . ' = ?', [$columnValue])
            ->execute();

        $row = $qb->fetch('cnt');

        return $row > 0;
    }

    /**
     * Returns a new instance of QueryBuilder
     */
    private function qb(string $method = __METHOD__): QueryBuilder {
        return new QueryBuilder($this->conn, $this->logger, $method);
    }
}

?>