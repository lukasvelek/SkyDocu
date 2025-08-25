<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_25_0027_application_log extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->create('application_log')
            ->primaryKey('logId')
            ->varchar('contextId')
            ->text('callstack', true)
            ->text('caller')
            ->text('message')
            ->varchar('type')
            ->enum('level')
            ->datetimeAuto('dateCreated')
        ;

        return $schema;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        return $this->getTableSeeding();
    }
}

?>