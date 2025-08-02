<?php

namespace App\Data\Db\Migrations\Containers;

use App\Constants\Container\SystemGroups;
use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_06_22_0018_process_designers_group extends AContainerBaseMigration {
    public function up(): TableSchema {
        return $this->getTableSchema();
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        if(!$this->checkValueExistsInTable('groups', 'title', SystemGroups::PROCESS_DESIGNERS)) {
            $seed->seed('groups')
                ->add([
                    'groupId' => $this->getId('groups'),
                    'title' => SystemGroups::PROCESS_DESIGNERS
                ]);
        }

        return $seed;
    }
}

?>