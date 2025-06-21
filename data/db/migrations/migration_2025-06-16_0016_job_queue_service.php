<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;
use App\Helpers\BackgroundServiceScheduleHelper;

class migration_2025_06_16_0016_job_queue_service extends ABaseMigration {
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
                'serviceId' => $this->getId('system_services'),
                'title' => 'JobQueue',
                'scriptPath' => 'job_queue_service.php',
                'schedule' => BackgroundServiceScheduleHelper::createScheduleFromForm([
                    'mon' => true,
                    'tue' => true,
                    'wed' => true,
                    'thu' => true,
                    'fri' => true,
                    'sat' => true,
                    'sun' => true
                ], '2')
            ]);

        return $seed;
    }
}

?>