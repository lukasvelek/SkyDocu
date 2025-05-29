<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

/**
 * This migration contains database schema updates for external system tokens
 * 
 * @author Lukas Velek
 * @version 1.0 from 03/26/2025
 */
class migration_2025_03_26_0003_external_system_tokens extends AContainerBaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('external_system_tokens')
            ->primaryKey('tokenId')
            ->varchar('systemId')
            ->varchar('token')
            ->datetime('dateValidUntil')
            ->datetimeAuto('dateCreated')
            ->index(['token']);

        return $table;
    }

    public function down(): TableSchema {
        $table = $this->getTableSchema();

        return $table;
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        return $seed;
    }
}

?>