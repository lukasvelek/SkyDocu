<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

/**
 * Preparation for external systems
 * 
 * @author Lukas Velek
 * @version 1.0 from 03/25/2025
 */
class migration_2025_03_25_0002_external_system_creation extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('external_systems')
            ->primaryKey('systemId')
            ->varchar('title')
            ->varchar('description')
            ->varchar('login')
            ->varchar('password')
            ->bool('isEnabled')
            ->default('isEnabled', 1)
            ->datetimeAuto('dateCreated');

        $table->create('external_system_log')
            ->primaryKey('entryId')
            ->varchar('systemId')
            ->varchar('message')
            ->integer('actionType', 4)
            ->integer('objectType', 4)
            ->datetimeAuto('dateCreated')
            ->index(['systemId']);

        $table->create('external_system_rights')
            ->primaryKey('rightId')
            ->varchar('systemId')
            ->varchar('operationName')
            ->bool('isEnabled')
            ->index(['systemId']);

        return $table;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        return $seed;
    }
}

?>