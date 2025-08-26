<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_28_0028_process_instance_log extends AContainerBaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->create('process_instance_log')
            ->primaryKey('logId')
            ->varchar('instanceId')
            ->text('message')
            ->varchar('userId')
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