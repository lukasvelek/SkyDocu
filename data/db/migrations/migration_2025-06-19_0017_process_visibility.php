<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_06_19_0017_process_visibility extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->update('processes')
            ->bit('isVisible')
            ->default('isVisible', 1);

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