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
            ->text('data')
            ->varchar('currentOfficerId', 256, true)
            ->integer('currentOfficerType', 4)
            ->integer('status', 4)
            ->datetimeAuto('dateCreated')
            ->datetime('dateModified', true);

        $table->create('processes')
            ->primaryKey('processId')
            ->varchar('uniqueProcessId')
            ->varchar('title')
            ->varchar('description')
            ->text('form')
            ->text('workflow', true)
            ->text('workflowConfiguration', true)
            ->varchar('userId')
            ->integer('status', 4)
            ->datetimeAuto('dateCreated')
            ->varchar('colorCombo', 256, true);

        return $table;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $processMetadata = $seed->seed('process_metadata');

        $processMetadata->deleteAll();

        return $seed;
    }
}

?>