<?php

namespace App\Authorizators;

use App\Constants\Container\SystemGroups;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Managers\Container\GroupManager;

/**
 * SupervisorAuthorizator is used for authorizing supervisors
 * 
 * @author Lukas Velek
 */
class SupervisorAuthorizator extends AAuthorizator {
    private GroupManager $groupManager;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $db DatabaseConnection instance
     * @param Logger $logger Logger instance
     * @param GroupManager $groupManager GroupManager instance
     */
    public function __construct(DatabaseConnection $db, Logger $logger, GroupManager $groupManager) {
        parent::__construct($db, $logger);

        $this->groupManager = $groupManager;
    }

    /**
     * Checks if user can view all processes view
     * 
     * @param string $userId User ID
     */
    public function canUserViewAllProcesses(string $userId): bool {
        $users = $this->groupManager->getUsersForGroupTitle(SystemGroups::PROCESS_SUPERVISOR);

        return in_array($userId, $users);
    }
}

?>