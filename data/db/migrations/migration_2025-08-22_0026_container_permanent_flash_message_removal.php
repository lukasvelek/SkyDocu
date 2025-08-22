<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_22_0026_container_permanent_flash_message_removal extends ABaseMigration {
    public function up(): TableSchema {
        $seed = $this->getTableSchema();

        $seed->update('containers')
            ->removeColumn('permanentFlashMessage');

        return $seed;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        return $this->getTableSeeding();
    }
}

?>