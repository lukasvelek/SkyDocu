<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_04_18_0005_old_processes_removal extends AContainerBaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->drop('processes');
        $table->drop('process_data');
        $table->drop('process_types');

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