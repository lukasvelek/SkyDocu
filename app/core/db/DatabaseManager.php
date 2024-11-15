<?php

namespace App\Core\DB;

use App\Core\DatabaseConnection;
use App\Exceptions\AException;
use App\Exceptions\DatabaseExecutionException;
use App\Logger\Logger;
use Error;
use Exception;

/**
 * DatabaseManager allows managing databases and database tables - e.g. enables manipulation with the structure.
 * 
 * @author Lukas Velek
 */
class DatabaseManager {
    private DatabaseConnection $db;
    private Logger $logger;

    private array $connections;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $db Master database connection instance
     * @param Logger $logger Logger instance
     */
    public function __construct(DatabaseConnection $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;

        $this->connections = [];
    }

    /**
     * Creates a new database
     * 
     * @param string $name Database name
     * @return True True on success
     * @throws DatabaseExecutionException
     */
    public function createNewDatabase(string $name) {
        $sql = "CREATE DATABASE `" . $name . "`;";

        $result = false;
        try {
            $result = $this->db->query($sql);
        } catch(Error|Exception $e) {
            $result = false;
        }

        if($result !== false) {
            return true;
        } else {
            throw new DatabaseExecutionException('Could not create database.', $sql);
        }
    }

    /**
     * Creates a connection to a database
     * 
     * @param string $name Database name
     * @return DatabaseConnection DatabaseConnection instance
     */
    public function getConnectionToDatabase(string $name) {
        try {
            if(array_key_exists($name, $this->connections)) {
                return $this->connections[$name];
            }

            $conn = new DatabaseConnection($name);
            $this->connections[$name] = $conn;
            return $conn;
        } catch(AException $e) {
            throw $e;
        }
    }

    /**
     * Creates a table
     * 
     * @param string $name Table name
     * @param array $columns Column definition
     * @param string $dbName Database name
     * @return bool True on success
     */
    public function createTable(string $name, array $columns, string $dbName) {
        try {
            $conn = $this->getConnectionToDatabase($dbName);
        } catch(AException $e) {
            throw $e;
        }

        // COLUMN PROCESSING
        $processedColumns = [];
        foreach($columns as $colName => $definition) {
            $processedColumns[] = $colName . ' ' . $definition;
        }
        // END OF COLUMN PROCESSING

        $sql = "CREATE TABLE IF NOT EXISTS `$name` (" . implode(', ', $processedColumns) . ");";

        try {
            $result = $conn->query($sql);

            if($result !== false) {
                return true;
            } else {
                throw new DatabaseExecutionException('Could not create table.', $sql);
            }
        } catch(AException $e) {
            throw $e;
        }
    }

    /**
     * Inserts data to a table
     * 
     * @param string $name Table name
     * @param array $data Data
     * @param string $dbName Database name
     * @return bool True on success
     */
    public function insertDataToTable(string $name, array $data, string $dbName) {
        try {
            $conn = $this->getConnectionToDatabase($dbName);
        } catch(AException $e) {
            throw $e;
        }

        $keys = [];
        $values = [];
        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        $sql = "INSERT INTO `$name` (" . implode(', ', $keys) . ") VALUES ('" . implode('\', \'', $values) . "');";

        try {
            $result = $conn->query($sql);

            if($result !== false) {
                return true;
            } else {
                throw new DatabaseExecutionException('Could not insert data to table.', $sql);
            }
        } catch(AException $e) {
            throw $e;
        }
    }

    /**
     * Drops database
     * 
     * @param string $databaseName Database name
     * @return bool True on success
     */
    public function dropDatabase(string $databaseName) {
        try {
            $conn = $this->getConnectionToDatabase($databaseName);
        } catch(AException $e) {
            throw $e;
        }

        $sql = "DROP DATABASE `" . $databaseName . "`";

        try {
            $result = $conn->query($sql);

            if($result !== false) {
                return true;
            } else {
                throw new DatabaseExecutionException('Could not drop database.', $sql);
            }
        } catch(AException $e) {
            throw $e;
        }
    }
}

?>