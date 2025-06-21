<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_04_13_0006_users_own_datetime_format extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->update('users')
            ->varchar('dateFormat')
            ->default('dateFormat', 'd.m.Y')
            ->varchar('timeFormat')
            ->default('timeFormat', 'H:i:s');

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