<?php

namespace App\Core;

use App\Constants\SystemGroups;
use App\Core\DB\DatabaseMigrationManager;
use App\Logger\Logger;

/**
 * Installs the database - creates tables, indexes
 * 
 * @author Lukas Velek
 */
class DatabaseInstaller {
    private DatabaseConnection $db;
    private Logger $logger;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $db DatabaseConnection instance
     * @param Logger $logger Logger instance
     */
    public function __construct(DatabaseConnection $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Performs the database installation
     */
    public function install() {
        $this->logger->info('Database installation started.', __METHOD__);

        $this->createTables();
        //$this->createIndexes();
        //$this->createUsers();
        //$this->createGroupsAndTheirMembers();
        //$this->addSystemServices();

        $this->logger->info('Database installation finished.', __METHOD__);
    }

    private function createTables() {
        $this->logger->info('Creating tables.', __METHOD__);

        $migrationManager = new DatabaseMigrationManager($this->db, null, $this->logger);
        $migrationManager->runMigrations();

        $this->logger->info('Table creation finished.', __METHOD__);
    }

    /**
     * Creates default users
     */
    private function createUsers() {
        $this->logger->info('Creating users.', __METHOD__);

        $users = [
            'admin' => 'admin',
            'service_user' => 'service_user'
        ];

        $i = 0;
        foreach($users as $username => $password) {
            $password = password_hash($password, PASSWORD_BCRYPT);
            $userId = HashManager::createEntityId();
            $fullname = ucfirst($username);

            $sql = 'INSERT INTO `users` (`userId`, `username`, `password`, `fullname`, `isTechnical`)
                    SELECT \'' . $userId . '\', \'' . $username . '\', \'' . $password . '\', \'' . $fullname . '\', 1
                    WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = \'' . $username . '\')';

            $this->db->query($sql);

            $i++;
        }

        $this->logger->info('Created ' . $i . ' users.', __METHOD__);
    }

    /**
     * Creates system groups and adds the default members to them
     */
    private function createGroupsAndTheirMembers() {
        $this->logger->info('Creating groups and adding users to them.', __METHOD__);

        $groups = [
            SystemGroups::SUPERADMINISTRATORS => [
                'admin'
            ],
            SystemGroups::CONTAINER_MANAGERS => [
                'admin'
            ]
        ];

        $i = 0;
        foreach($groups as $group => $members) {
            $groupId = HashManager::createEntityId();

            $sql = 'INSERT INTO `groups` (`groupId`, `title`)
                    SELECT \'' . $groupId . '\', \'' . $group . '\'
                    WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE `title` = \'' . $group . '\')';

            $this->db->query($sql);

            foreach($members as $username) {
                $sql = 'SELECT `userId` FROM `users` WHERE `username` = \'' . $username . '\'';

                $rows = $this->db->query($sql);

                $userId = null;
                foreach($rows as $row) {
                    $userId = $row['userId'];
                }

                if($userId !== null) {
                    $groupUserId = HashManager::createEntityId();

                    $sql = 'INSERT INTO `group_users` (`groupUserId`, `groupId`, `userId`)
                            SELECT \'' . $groupUserId . '\', \'' . $groupId . '\', \'' . $userId . '\'
                            WHERE NOT EXISTS (SELECT 1 FROM `group_users` WHERE `groupId` = \'' . $groupId . '\' AND `userId` = \'' . $userId . '\')';

                    $this->db->query($sql);
                }
            }

            $i++;
        }

        $this->logger->info('Created ' . $i . ' groups.', __METHOD__);
    }

    /**
     * Adds system services
     */
    private function addSystemServices() {
        $this->logger->info('Adding system services.', __METHOD__);

        $services = [
            'ContainerCreationMaster' => 'container_creation_master.php',
            'LogRotate' => 'log_rotate_service.php',
            'ContainerUsageStatistics' => 'container_usage_statistics_service.php',
            //'ContainerStandaloneProcessChecker' => 'container_standalone_process_checker_service.php',
            'ProcessSubstitute' => 'process_substitute_service.php',
            'ContainerOrphanedFilesRemovingMaster' => 'cofrs_master.php'
        ];

        $serviceIds = [];
        foreach($services as $title => $path) {
            $id = HashManager::createEntityId();
            $serviceIds[$title] = $id;

            $arr = [
                'schedule' => [
                    'days' => 'mon;tue;wed;thu;fri;sat;sun',
                    'every' => '10'
                ]
            ];

            if($title == 'ContainerCreationMaster') {
                $arr['schedule']['every'] = '5';
            }

            $schedule = json_encode($arr, JSON_FORCE_OBJECT);

            $sql = "INSERT INTO `system_services` (`serviceId`, `title`, `scriptPath`, `schedule`)
                    SELECT '$id', '$title', '$path', '$schedule'
                    WHERE NOT EXISTS (SELECT 1 FROM `system_services` WHERE serviceId = '$id' AND title = '$title' AND scriptPath = '$path' AND schedule = '$schedule')";

            $this->db->query($sql);
        }

        $childServices = [
            'ContainerCreationMaster' => [
                'ContainerCreationSlave' => 'container_creation_slave.php'
            ],
            'ContainerOrphanedFilesRemovingMaster' => [
                'ContainerOrphanedFilesRemovingSlave' => 'cofrs_slave.php'
            ]
        ];

        foreach($childServices as $masterService => $children) {
            $masterId = $serviceIds[$masterService];

            foreach($children as $title => $path) {
                $id = HashManager::createEntityId();

                $sql = "INSERT INTO `system_services` (`serviceId`, `title`, `scriptPath`, `parentServiceId`)
                        SELECT '$id', '$title', '$path', '$masterId'
                        WHERE NOT EXISTS (SELECT 1 FROM `system_services` WHERE serviceId = '$id' AND title = '$title' AND scriptPath = '$path' AND parentServiceId = '$masterId')";

                $this->db->query($sql);
            }
        }

        $this->logger->info('Added system services.', __METHOD__);
    }
}

?>