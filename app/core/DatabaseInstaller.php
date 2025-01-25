<?php

namespace App\Core;

use App\Constants\SystemGroups;
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
        $this->createIndexes();
        $this->createUsers();
        $this->createGroupsAndTheirMembers();
        $this->addSystemServices();

        $this->logger->info('Database installation finished.', __METHOD__);
    }

    /**
     * Creates tables
     */
    private function createTables() {
        $this->logger->info('Creating tables.', __METHOD__);

        $tables = [
            'users' => [
                'userId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'username' => 'VARCHAR(256) NOT NULL',
                'password' => 'VARCHAR(256) NOT NULL',
                'fullname' => 'VARCHAR(256) NOT NULL',
                'loginHash' => 'VARCHAR(256) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'email' => 'VARCHAR(256) NULL'
            ],
            'groups' => [
                'groupId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL',
                'containerId' => 'VARCHAR(256) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'group_users' => [
                'groupUserId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'groupId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'containers' => [
                'containerId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'databaseName' => 'VARCHAR(256) NOT NULL',
                'status' => 'INT(4) NOT NULL DEFAULT 1',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'environment' => 'INT(4) NOT NULL',
                'canShowContainerReferent' => 'INT(2) NOT NULL DEFAULT 1',
                'permanentFlashMessage' => 'TEXT NULL'
            ],
            'container_creation_status' => [
                'statusId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'containerId' => 'VARCHAR(256) NOT NULL',
                'percentFinished' => 'INT(4) NOT NULL DEFAULT 0',
                'description' => 'TEXT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'container_status_history' => [
                'historyId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'containerId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'oldStatus' => 'INT(4) NOT NULL',
                'newStatus' => 'INT(4) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'transaction_log' => [
                'transactionId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'callingMethod' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'system_services' => [
                'serviceId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL',
                'scriptPath' => 'VARCHAR(256) NOT NULL',
                'dateStarted' => 'DATETIME NULL',
                'dateEnded' => 'DATETIME NULL',
                'status' => 'INT(4) NOT NULL DEFAULT 1'
            ],
            'system_services_history' => [
                'historyId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'serviceId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'status' => 'INT(4) NOT NULL'
            ],
            'container_usage_statistics' => [
                'entryId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'containerId' => 'VARCHAR(256) NOT NULL',
                'totalSqlQueries' => 'INT(32) NOT NULL',
                'averageTimeTaken' => 'VARCHAR(256) NOT NULL',
                'totalTimeTaken' => 'VARCHAR(256) NOT NULL',
                'date' => 'DATETIME NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'container_invites' => [
                'inviteId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'containerId' => 'VARCHAR(256) NOT NULL',
                'dateValid' => 'DATETIME NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'container_invite_usage' => [
                'entryId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'inviteId' => 'VARCHAR(256) NOT NULL',
                'containerId' => 'VARCHAR(256) NOT NULL',
                'data' => 'TEXT NOT NULL',
                'status' => 'INT(4) NOT NULL DEFAULT 1',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'user_absence' => [
                'absenceId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'dateFrom' => 'DATETIME NOT NULL',
                'dateTo' => 'DATETIME NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'active' => 'INT(2) NOT NULL DEFAULT 1'
            ],
            'user_substitutes' => [
                'entryId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'substituteUserId' => 'VARCHAR(256) NOT NULL'
            ]
        ];

        $i = 0;
        foreach($tables as $name => $values) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . $name . '` (';

            $tmp = [];

            foreach($values as $key => $value) {
                $tmp[] = $key . ' ' . $value;
            }

            $sql .= implode(', ', $tmp);

            $sql .= ')';
            
            $this->db->query($sql);
            $this->logger->sql($sql, __METHOD__, null);

            $i++;
        }

        $this->logger->info('Created ' . $i . ' tables.', __METHOD__);
    }

    /**
     * Creates indexes
     */
    private function createIndexes() {
        $this->logger->info('Creating indexes.', __METHOD__);

        $indexes = [
            'containers' => [
                'databaseName'
            ],
            'container_creation_status' => [
                'containerId'
            ],
            'container_status_history' => [
                'containerId'
            ],
            'container_usage_statistics' => [
                'containerId'
            ],
            'groups' => [
                'containerId'
            ],
            'group_users' => [
                'groupId',
                'userId'
            ],
            'system_services_history' => [
                'serviceId'
            ],
            'users' => [
                'username'
            ]
        ];

        $indexCount = [];
        foreach($indexes as $tableName => $columns) {
            $i = 1;

            if(isset($indexCount[$tableName])) {
                $i = $indexCount[$tableName] + 1;
            }

            $name = $tableName . '_i' . $i;

            $sql = "DROP INDEX IF EXISTS `$name` ON `$tableName`";

            $this->logger->sql($sql, __METHOD__, null);

            $this->db->query($sql);

            $cols = implode(', ', $columns);

            $sql = "CREATE INDEX $name ON $tableName ($cols)";

            $this->logger->sql($sql, __METHOD__, null);

            $this->db->query($sql);

            $indexCount[$tableName] = $i;
        }

        $this->logger->info('Created indexes.', __METHOD__);
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

            $sql = 'INSERT INTO `users` (`userId`, `username`, `password`, `fullname`)
                    SELECT \'' . $userId . '\', \'' . $username . '\', \'' . $password . '\', \'' . $fullname . '\'
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
            'ContainerCreation' => 'container_creation_service.php',
            'LogRotate' => 'log_rotate_service.php',
            'ContainerUsageStatistics' => 'container_usage_statistics_service.php'
        ];

        foreach($services as $title => $path) {
            $id = HashManager::createEntityId();

            $sql = "INSERT INTO `system_services` (`serviceId`, `title`, `scriptPath`)
                    SELECT '$id', '$title', '$path'
                    WHERE NOT EXISTS (SELECT 1 FROM `system_services` WHERE `serviceId` = '$id' AND title = '$title' AND scriptPath = '$path')";

            $this->db->query($sql);
        }

        $this->logger->info('Added system services.', __METHOD__);
    }
}

?>