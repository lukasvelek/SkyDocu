<?php

namespace App\Core\DB;

use App\Core\DatabaseConnection;
use App\Exceptions\GeneralException;

/**
 * AMultipleDatabaseConnectionHandler allows using multiple connections to the database server. Each connection uses different database.
 * 
 * @author Lukas Velek
 */
abstract class AMultipleDatabaseConnectionHandler {
    protected array $connections;

    public DatabaseConnection $conn;
    private DatabaseConnection $masterConnection;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $masterConnection Master database connection
     */
    protected function __construct(DatabaseConnection $masterConnection) {
        $this->connections = [];
        $this->conn = $masterConnection;
        $this->masterConnection = $masterConnection;
    }

    /**
     * Register database connection
     * 
     * @param string $dbName Database connection name
     * @param DatabaseConnection $conn Database connection
     */
    public function registerConnection(string $dbName, DatabaseConnection $conn) {
        $this->connections[$dbName] = $conn;
    }

    /**
     * Switches database connection
     * 
     * @param string $dbName Database connection name
     */
    public function useConnection(string $dbName) {
        if(!array_key_exists($dbName, $this->connections)) {
            throw new GeneralException('No connection to database \'' . $dbName . '\' exists.');
        }

        $this->conn = $this->connections[$dbName];
    }

    /**
     * Switches database connection to master
     */
    public function useMaster() {
        $this->conn = $this->masterConnection;
    }
}

?>