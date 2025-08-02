<?php

namespace App\Data\Db\Migrations\Containers;

use App\Constants\Container\SystemGroups;
use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;
use App\Managers\EntityManager;

/**
 * This migration contains database updates for company property management
 * 
 * @author Lukas Velek
 * @version 1.0 from 04/05/2025
 */
class migration_2025_04_05_0004_company_property_management extends AContainerBaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('property_items_user_relation')
            ->primaryKey('relationId')
            ->varchar('userId')
            ->varchar('itemId')
            ->bool('isActive')
            ->default('isActive', 1)
            ->datetimeAuto('dateCreated');

        $table->update('process_metadata_list_values')
            ->varchar('title2', 256, true)
            ->varchar('title3', 256, true);

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