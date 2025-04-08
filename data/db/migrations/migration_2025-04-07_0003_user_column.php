<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

/**
 * This migrations adds a new column to the 'users' table of type bool with name isDeleted and default value 0.
 * 
 * @author Lukas Velek
 * @version 1.0 from 04/07/2025
 */
class migration_2025_04_07_0003_user_column extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->update('users')
            ->bool('isDeleted')
            ->default('isDeleted', 0);

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