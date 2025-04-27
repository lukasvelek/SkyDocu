<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_04_27_0009_new_process_metadata extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->create('process_metadata')
            ->primaryKey('metadataId')
            ->varchar('uniqueProcessId')
            ->varchar('title')
            ->varchar('guiTitle')
            ->integer('type', 4)
            ->varchar('defaultValue', 256, true)
            ->integer('isRequired', 2)
            ->default('isRequired', 0)
            ->integer('isSystem', 2)
            ->default('isSystem', 0)
            ->index(['uniqueProcessId']);

        $schema->create('process_metadata_values')
            ->primaryKey('valueId')
            ->varchar('metadataId')
            ->varchar('metadataKey')
            ->varchar('sortingKey')
            ->varchar('title')
            ->index(['metadataId']);

        return $schema;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $seed->seed('process_metadata')
            ->add([
                'metadataId' => $this->getId('process_metadata'),
                'uniqueProcessId' => $this->getUniqueProcessIdForProcessTitle('Invoice'),
                'title' => 'companies',
                'guiTitle' => 'Companies',
                'type' => '1', // enum
                'isRequired' => '1',
                'isSystem' => '1'
            ]);

        return $seed;
    }
}

?>