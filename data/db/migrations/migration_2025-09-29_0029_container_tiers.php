<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_09_29_0029_container_tiers extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->update('containers')
            ->enum('tier')
            ->default('tier', 1)
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