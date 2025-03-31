<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

/**
 * This migration creats audit_log table
 * 
 * @author Lukas Velek
 * @version 1.0 from 03/31/2025
 */
class migration_2025_03_31_0003_audit_log extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('audit_log')
            ->primaryKey('entryId')
            ->varchar('containerId', 256, true)
            ->varchar('userId')
            ->integer('actionType', 4)
            ->integer('object1Type', 4, true)
            ->integer('object2Type', 4, true)
            ->integer('object3Type', 4, true)
            ->varchar('description')
            ->datetimeAuto('dateCreated')
        ;

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