<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_22_0028_container_permanent_flash_message_removal extends AContainerBaseMigration {
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