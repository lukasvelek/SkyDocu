<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_07_21_0020_new_user_metadata extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->update('users')
            ->varchar('orgPosition', 256, true)
            ->varchar('orgSection', 256, true)
            ->varchar('orgDepartment', 256, true);

        return $schema;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        return $this->getTableSeeding();
    }
}