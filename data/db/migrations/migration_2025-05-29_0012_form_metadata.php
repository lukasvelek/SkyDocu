<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_05_29_0012_form_metadata extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->update('processes')
            ->text('metadataDefinition', true);

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