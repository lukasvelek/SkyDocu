<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_07_16_0019_file_storage extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->create('file_storage')
            ->primaryKey('fileId')
            ->varchar('filename')
            ->text('filepath')
            ->integer('filesize')
            ->varchar('userId')
            ->varchar('hash')
            ->datetimeAuto('dateCreated');

        $schema->update('users')
            ->varchar('profilePictureFileId', 256, true);

        return $schema;
    }

    public function down(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->drop('file_storage');

        return $schema;
    }

    public function seeding(): TableSeeding {
        return $this->getTableSeeding();
    }
}