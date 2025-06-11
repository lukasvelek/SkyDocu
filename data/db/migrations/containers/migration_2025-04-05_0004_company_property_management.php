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
        $seed = $this->getTableSeeding();

        if($this->getValueFromTableByConditions(EntityManager::C_GROUPS, 'groupId', 'title', SystemGroups::PROPERTY_MANAGERS) === null) {
            $groupSeed = $seed->seed(EntityManager::C_GROUPS);
        
            $groupSeed->add([
                'groupId' => $this->getId(EntityManager::C_GROUPS),
                'title' => SystemGroups::PROPERTY_MANAGERS
            ]);
        }

        /*$processMetadata = $seed->seed(EntityManager::C_PROCESS_CUSTOM_METADATA);

        $processMetadata->add([
            'metadataId' => $this->getId(EntityManager::C_PROCESS_CUSTOM_METADATA),
            'typeId' => $this->getValueFromTableByConditions(
                EntityManager::C_PROCESS_TYPES,
                'typeId',
                'typeKey',
                StandaloneProcesses::REQUEST_PROPERTY_MOVE
            ),
            'title' => 'items',
            'guiTitle' => 'Items',
            'type' => CustomMetadataTypes::ENUM,
            'isRequired' => 1
        ]);*/

        return $seed;
    }
}

?>