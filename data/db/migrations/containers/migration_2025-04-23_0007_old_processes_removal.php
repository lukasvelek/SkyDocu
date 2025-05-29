<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_04_23_0007_old_processes_removal extends AContainerBaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->drop('property_items_user_relation');
        $table->drop('process_metadata_history');
        $table->drop('process_comments');

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