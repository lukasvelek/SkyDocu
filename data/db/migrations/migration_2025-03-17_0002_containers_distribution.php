<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

/**
 * This migration adds a boolean type column to `containers` database table.
 * 
 * @author Lukas Velek
 * @version 1.0 from 03/17/2025
 */
class migration_2025_03_17_0002_containers_distribution extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->update('containers')
            ->bool('isInDistribution')
            ->default('isInDistribution', 1);

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