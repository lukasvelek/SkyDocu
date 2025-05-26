<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_05_26_0011_multiform_processes_bg_service extends ABaseMigration {
    public function up(): TableSchema {
        return $this->getTableSchema();
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $seed->seed('system_services')
            ->add([
                'serviceId' => $this->getId('system_services', 'serviceId'),
                'title' => 'ProcessServiceUserHandling',
                'scriptPath' => 'psuhs.php',
                'isEnabled' => 0,
                'schedule' => null
            ]);

        return $seed;
    }
}

?>