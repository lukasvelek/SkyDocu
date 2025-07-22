<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_07_22_0022_global_external_systems extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->create('external_systems')
            ->primaryKey('systemId')
            ->varchar('containerId')
            ->varchar('title')
            ->text('description')
            ->varchar('login')
            ->varchar('password')
            ->bool('isEnabled')
            ->datetimeAuto('dateCreated')
            ->default('isEanbled', 1)
            ->index(['containerId']);

        $schema->create('external_system_tokens')
            ->primaryKey('tokenId')
            ->varchar('containerId')
            ->varchar('systemId')
            ->text('token')
            ->datetime('dateValidUntil')
            ->datetimeAuto('dateCreated')
            ->index(['systemId'])
            ->index(['containerId'])
            ->index(['token']);

        $schema->create('external_system_log')
            ->primaryKey('entryId')
            ->varchar('systemId')
            ->text('message')
            ->enum('actionType')
            ->enum('objectType')
            ->datetimeAuto('dateCreated')
            ->index(['systemId'])
            ->index(['actionType', 'objectType']);

        $schema->create('external_system_rights')
            ->varchar('systemId')
            ->varchar('operationName')
            ->bool('isEnabled')
            ->index(['systemId']);

        return $schema;
    }

    public function down(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->drop('external_systems');
        $schema->drop('external_system_tokens');
        $schema->drop('external_system_log');
        $schema->drop('external_system_rights');

        return $schema;
    }

    public function seeding(): TableSeeding {
        return $this->getTableSeeding();
    }
}