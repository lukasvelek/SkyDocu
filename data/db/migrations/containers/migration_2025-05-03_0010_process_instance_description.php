<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_05_03_0010_process_instance_description extends AContainerBaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->update('process_instances')
            ->varchar('description', 256)
            ->default('description', 'Process instance');

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