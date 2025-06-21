<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_05_24_0013_multiform_processes2 extends AContainerBaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('processes')
            ->primaryKey('processId')
            ->varchar('uniqueProcessId')
            ->varchar('title')
            ->varchar('description')
            ->varchar('userId')
            ->text('definition', true)
            ->integer('status', 4)
            ->datetimeAuto('dateCreated');

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