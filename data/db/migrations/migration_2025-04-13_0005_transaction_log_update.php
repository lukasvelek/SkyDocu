<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

/**
 * This migration adds a new column 'containerId' to the 'transaction_log' table
 * 
 * @author Lukas Velek
 * @version 1.0 from 04/13/2025
 */
class migration_2025_04_13_0005_transaction_log_update extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->update('transaction_log')
            ->varchar('containerId', 256, true);

        return $table;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        return $this->getTableSeeding();
    }
}

?>