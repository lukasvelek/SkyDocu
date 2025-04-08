<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

/**
 * This migrations adds a new column to the 'users' table of type datetime with name dateModified.
 * 
 * @author Lukas Velek
 * @version 1.0 from 04/07/2025
 */
class migration_2025_04_07_0004_user_table_update extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->update('users')
            ->datetime('dateModified', true);

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