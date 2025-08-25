<?php

use App\Core\Application;
use App\Core\DB\DatabaseMigrationManager;
use App\Logger\Logger;

require_once('../config.php');
require_once('../app/app_loader.php');

try {
    $app = new Application();

    $logger = new Logger();
    $logger->setFilename('run_master_migrations');

    $dmm = new DatabaseMigrationManager($app->db, null, $logger);

    $dmm->runMigrations(true);
} catch(\Exception $e) {
    echo $e->getMessage();
}