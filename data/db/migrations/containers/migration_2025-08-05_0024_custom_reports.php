<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_05_0024_custom_reports extends AContainerBaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('reports')
            ->primaryKey('reportId')
            ->varchar('title')
            ->text('description', true)
            ->text('definition')
            ->varchar('userId')
            ->datetimeAuto('dateCreated')
            ->enum('status')
            ->default('status', 1)
            ->index(['status'])
        ;

        $table->create('report_rights')
            ->primaryKey('rightId')
            ->varchar('reportId')
            ->varchar('entityId')
            ->enum('entityType')
            ->datetimeAuto('dateCreated')
            ->index(['reportId'])
            ->index(['entityId', 'entityType'])
        ;

        return $table;
    }

    public function down(): TableSchema {
        $table = $this->getTableSchema();

        $table->drop('reports');
        $table->drop('report_rights');

        return $table;
    }

    public function seeding(): TableSeeding {
        return $this->getTableSeeding();
    }
}

?>