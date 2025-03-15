<?php

namespace App\Data\Db\Migrations;

use App\Constants\SystemGroups;
use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_03_15_0001_initial extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('users')
            ->primaryKey('userId')
            ->varchar('username')
            ->varchar('password')
            ->varchar('fullname')
            ->varchar('loginHash', 256, true)
            ->datetimeAuto('dateCreated')
            ->varchar('email', 256, true)
            ->bool('isTechnical')
            ->default('isTechnical', 0)
            ->integer('appDesignTheme', 4)
            ->default('appDesignTheme', 0)
            ->index(['username']);

        $table->create('groups')
            ->primaryKey('groupId')
            ->varchar('title')
            ->varchar('containerId', 256, true)
            ->datetimeAuto('dateCreated')
            ->index(['containerId']);

        $table->create('group_users')
            ->primaryKey('groupUserId')
            ->varchar('groupId')
            ->varchar('userId')
            ->datetimeAuto('dateCreated')
            ->index(['groupId', 'userId']);

        $table->create('containers')
            ->primaryKey('containerId')
            ->varchar('title')
            ->text('description', true)
            ->varchar('userId')
            ->integer('status', 4)
            ->default('status', 1)
            ->datetimeAuto('dateCreated')
            ->integer('environment', 4)
            ->bool('canShowContainerReferent')
            ->default('canShowContainerReferent', 1)
            ->text('permanentFlashMessage', true);

        $table->create('container_databases')
            ->primaryKey('entryId')
            ->varchar('containerId')
            ->varchar('name')
            ->bool('isDefault')
            ->default('isDefault', 0)
            ->varchar('title')
            ->text('description')
            ->integer('dbSchema')
            ->default('dbSchema', 0)
            ->index(['containerId']);

        $table->create('container_database_tables')
            ->primaryKey('entryId')
            ->varchar('containerId')
            ->varchar('databaseId')
            ->varchar('name')
            ->bool('isCreated')
            ->default('isCreated', 0)
            ->index(['containerId', 'databaseId']);

        $table->create('container_database_table_columns')
            ->primaryKey('entryId')
            ->varchar('containerId')
            ->varchar('databaseId')
            ->varchar('tableId')
            ->varchar('name')
            ->varchar('title')
            ->varchar('definition')
            ->index(['containerId', 'databaseId', 'tableId']);

        $table->create('container_creation_status')
            ->primaryKey('statusId')
            ->varchar('containerId')
            ->integer('percentFinished', 4)
            ->default('percentFinished', 0)
            ->text('description', true)
            ->datetimeAuto('dateCreated')
            ->index(['containerId']);

        $table->create('container_status_history')
            ->primaryKey('historyId')
            ->varchar('containerId')
            ->varchar('userId')
            ->text('description')
            ->integer('oldStatus', 4)
            ->integer('newStatus', 4)
            ->datetimeAuto('dateCreated')
            ->index(['containerId']);

        $table->create('transaction_log')
            ->primaryKey('transactionId')
            ->varchar('userId')
            ->text('callingMethod')
            ->datetimeAuto('dateCreated');

        $table->create('system_services')
            ->primaryKey('serviceId')
            ->varchar('title')
            ->varchar('scriptPath')
            ->datetime('dateStarted', true)
            ->datetime('dateEnded', true)
            ->integer('status', 4)
            ->default('status', 1)
            ->varchar('parentServiceId', 256, true)
            ->bool('isEnabled')
            ->default('isEnabled', 1)
            ->varchar('schedule', 512, true);

        $table->create('system_services_history')
            ->primaryKey('historyId')
            ->varchar('serviceId')
            ->text('args')
            ->datetimeAuto('dateCreated')
            ->integer('status', 4)
            ->text('exception', true)
            ->index(['serviceId']);

        $table->create('container_usage_statistics')
            ->primaryKey('entryId')
            ->varchar('containerId')
            ->integer('totalSqlQueries')
            ->varchar('averageTimeTaken')
            ->varchar('totalTimeTaken')
            ->datetime('date')
            ->datetimeAuto('dateCreated')
            ->index(['containerId']);

        $table->create('container_invites')
            ->primaryKey('inviteId')
            ->varchar('containerId')
            ->datetime('dateValid')
            ->datetimeAuto('dateCreated')
            ->index(['containerId']);

        $table->create('container_invite_usage')
            ->primaryKey('entryId')
            ->varchar('inviteId')
            ->varchar('containerId')
            ->text('data')
            ->integer('status', 4)
            ->default('status', 1)
            ->datetimeAuto('dateCreated')
            ->index(['containerId', 'inviteId']);

        $table->create('user_absence')
            ->primaryKey('absenceId')
            ->varchar('userId')
            ->datetime('dateFrom')
            ->dateTime('dateTo')
            ->datetimeAuto('dateCreated')
            ->bool('active')
            ->default('active', 1)
            ->index(['userId']);

        $table->create('user_substitutes')
            ->primaryKey('entryId')
            ->varchar('userId')
            ->varchar('substituteUserId')
            ->index(['userId', 'substituteUserId']);

        return $table;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $userIds = [
            'admin' => $this->getId('users', 'userId'),
            'service_user' => $this->getId('users', 'userId')
        ];

        $seed->seed('users')
            ->add([
                'userId' => $userIds['admin'],
                'username' => 'admin',
                'password' => password_hash('admin', PASSWORD_BCRYPT),
                'fullname' => 'Administrator',
                'isTechnical' => '1'
            ])
            ->add([
                'userId' => $userIds['service_user'],
                'username' => 'service_user',
                'password' => password_hash('service_user', PASSWORD_BCRYPT),
                'fullname' => 'service_user',
                'isTechnical' => '1'
            ]);

        $groupIds = [
            SystemGroups::SUPERADMINISTRATORS => $this->getId('groups', 'groupId'),
            SystemGroups::CONTAINER_MANAGERS => $this->getId('groups', 'groupId')
        ];

        $seed->seed('groups')
            ->add([
                'groupId' => $groupIds[SystemGroups::SUPERADMINISTRATORS],
                'title' => SystemGroups::SUPERADMINISTRATORS
            ])
            ->add([
                'groupId' => $groupIds[SystemGroups::CONTAINER_MANAGERS],
                'title' => SystemGroups::CONTAINER_MANAGERS
            ]);

        $seed->seed('group_users')
            ->add([
                'groupUserId' => $this->getId('group_users', 'groupUserId'),
                'groupId' => $groupIds[SystemGroups::SUPERADMINISTRATORS],
                'userId' => $userIds['admin']
            ])
            ->add([
                'groupUserId' => $this->getId('group_users', 'groupUserId'),
                'groupId' => $groupIds[SystemGroups::CONTAINER_MANAGERS],
                'userId' => $userIds['admin']
            ]);

        $services = [
            'ContainerCreationMaster' => 'container_creation_master.php',
            'LogRotate' => 'log_rotate_service.php',
            'ContainerUsageStatistics' => 'container_usage_statistics_service.php',
            'ProcessSubstitute' => 'process_substitute_service.php',
            'ContainerOrphanedFilesRemovingMaster' => 'cofrs_master.php'
        ];

        $serviceIds = [
            'ContainerCreationMaster' => $this->getId('system_services', 'serviceId'),
            'LogRotate' => $this->getId('system_services', 'serviceId'),
            'ContainerUsageStatistics' => $this->getId('system_services', 'serviceId'),
            'ProcessSubstitute' => $this->getId('system_services', 'serviceId'),
            'ContainerOrphanedFilesRemovingMaster' => $this->getId('system_services', 'serviceId')
        ];

        $serviceSeed = $seed->seed('system_services');

        foreach($services as $service => $path) {
            $arr = [
                'schedule' => [
                    'days' => 'mon;tue;wed;thu;fri;sat;sun',
                    'every' => '10'
                ]
            ];

            if($service == 'ContainerCreationMaster') {
                $arr['schedule']['every'] = '5';
            }

            $schedule = json_encode($arr, JSON_FORCE_OBJECT);

            $serviceSeed->add([
                'serviceId' => $serviceIds[$service],
                'title' => $service,
                'scriptPath' => $path,
                'schedule' => $schedule
            ]);
        }

        $childServices = [
            'ContainerCreationMaster' => [
                'ContainerCreationSlave' => 'container_creation_slave.php'
            ],
            'ContainerOrphanedFilesRemovingMaster' => [
                'ContainerOrphanedFilesRemovingSlave' => 'cofrs_slave.php'
            ]
        ];

        foreach($childServices as $masterService => $services) {
            $masterId = $serviceIds[$masterService];

            foreach($services as $service => $path) {
                $id = $this->getId('system_services', 'serviceId');

                $serviceSeed->add([
                    'serviceId' => $id,
                    'title' => $service,
                    'scriptPath' => $path,
                    'parentServiceId' => $masterId
                ]);
            }
        }

        return $seed;
    }
}

?>