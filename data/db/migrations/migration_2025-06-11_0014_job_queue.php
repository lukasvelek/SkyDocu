<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_06_11_0014_job_queue extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('job_queue')
            ->primaryKey('jobId')
            ->enum('type')
            ->enum('status')
            ->text('params')
            ->text('statusText')
            ->datetimeAuto('dateCreated')
            ->datetime('dateModified');

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