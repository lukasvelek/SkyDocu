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

    protected DatabaseConnection $conn;
    private DatabaseConnection $masterConnection;

    protected function __construct(DatabaseConnection $masterConnection) {
        $this->connections = [];
        $this->conn = $masterConnection;
        $this->masterConnection = $masterConnection;
    }

    public function registerConnection(string $dbName, DatabaseConnection $conn) {
        $this->connections[$dbName] = $conn;
    }

    public function useConnection(string $dbName) {
        if(!array_key_exists($dbName, $this->connections)) {
            throw new GeneralException('No connection to database \'' . $dbName . '\' exists.');
        }

        $this->conn = $this->connections[$dbName];
    }

    public function useMaster() {
        $this->conn = $this->masterConnection;
    }
}

?>