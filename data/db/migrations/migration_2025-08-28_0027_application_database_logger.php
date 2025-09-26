<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_28_0027_application_database_logger extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->create('application_log')
            ->primaryKey('logId')
            ->text('message')
            ->text('stackTrace', true)
            ->varchar('userId', 256, true)
            ->varchar('type')
            ->varchar('method')
            ->datetimeAuto('dateCreated')
            ->integer('tsCreated', 64)
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