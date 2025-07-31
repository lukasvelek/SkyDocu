<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_07_31_0023_process_metadata_values_deleting extends AContainerBaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->update('process_metadata_values')
            ->bool('isDeleted')
            ->default('isDeleted', 0)
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