<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_06_21_0017_service_user_processing extends AContainerBaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->update('process_instances')
            ->enum('systemStatus', true);

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