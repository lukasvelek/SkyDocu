<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_04_18_0006_new_processes extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('process_instances')
            ->primaryKey('instanceId')
            ->varchar('processId')
            ->varchar('userId')
            ->varchar('data')
            ->integer('status', 4)
            ->datetimeAuto('dateCreated');

        $table->create('processes')
            ->primaryKey('processId')
            ->varchar('uniqueProcessId')
            ->varchar('title')
            ->varchar('description')
            ->varchar('form', 32768)
            ->varchar('userId')
            ->integer('status', 4)
            ->datetimeAuto('dateCreated');

        return $table;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        

        return $seed;
    }
}

?>