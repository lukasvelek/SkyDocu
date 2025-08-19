<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_19_0023_automatic_user_deleting extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->update('users')
            ->datetime('dateDeleted', true);

        return $schema;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $seed->seed('system_services')
            ->add([
                'serviceId' => $this->getId(),
                'title' => 'UserDeleting',
                'scriptPath' => 'user_deleting.php'
            ])
        ;

        return $seed;
    }
}