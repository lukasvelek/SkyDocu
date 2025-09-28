<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_22_0025_container_permanent_flash_messages extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->create('container_permanent_flash_messages')
            ->primaryKey('messageId')
            ->varchar('userId')
            ->varchar('message')
            ->enum('type')
            ->datetimeAuto('dateCreated')
            ->datetime('dateValidUntil')
            ->bool('isActive')
            ->default('isActive', 1)
            ->index(['dateValidUntil']);

        return $schema;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        return $this->getTableSeeding();
    }
}