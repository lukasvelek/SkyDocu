<?php

namespace App\Data\Db\Migrations\Containers;

use App\Constants\Container\SystemGroups;
use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;
use App\Managers\EntityManager;

/**
 * This migration contains database updates for company property management
 * 
 * @author Lukas Velek
 * @version 1.0 from 04/05/2025
 */
class migration_2025_04_05_0004_company_property_management extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        return $table;
    }
    
    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $groupSeed = $seed->seed(EntityManager::C_GROUPS);
        
        $groupSeed->add([
            'groupId' => $this->getId(EntityManager::C_GROUPS),
            'title' => SystemGroups::PROPERTY_MANAGERS
        ]);

        return $seed;
    }
}

?>