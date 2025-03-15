<?php

namespace App\Core;

use App\Core\DB\DatabaseMigrationManager;
use App\Logger\Logger;

/**
 * Installs the database - creates tables, indexes
 * 
 * @author Lukas Velek
 */
class DatabaseInstaller {
    private DatabaseConnection $db;
    private Logger $logger;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $db DatabaseConnection instance
     * @param Logger $logger Logger instance
     */
    public function __construct(DatabaseConnection $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Performs the database installation
     */
    public function install() {
        $this->logger->info('Database installation started.', __METHOD__);

        $this->runMigrations();

        $this->logger->info('Database installation finished.', __METHOD__);
    }

    private function runMigrations() {
        $this->logger->info('Creating tables.', __METHOD__);

        $migrationManager = new DatabaseMigrationManager($this->db, null, $this->logger);
        $migrationManager->runMigrations();

        $this->logger->info('Table creation finished.', __METHOD__);
    }
}

?>