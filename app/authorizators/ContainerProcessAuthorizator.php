<?php

namespace App\Authorizators;

use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessInstanceManager;
use App\Managers\Container\ProcessManager;
use App\Managers\UserManager;

/**
 * ContainerProcessAuthorizator contains useful methods for checking rights on processes
 * 
 * @author Lukas Velek
 */
class ContainerProcessAuthorizator extends AAuthorizator {
    private ProcessManager $processManager;
    private ProcessInstanceManager $processInstanceManager;
    private GroupManager $groupManager;
    private UserManager $userManager;
    
    public function __construct(DatabaseConnection $conn, Logger $logger, ProcessManager $processManager, ProcessInstanceManager $processInstanceManager, GroupManager $groupManager, UserManager $userManager) {
        parent::__construct($conn, $logger);

        $this->processManager = $processManager;
        $this->processInstanceManager = $processInstanceManager;
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
    }

    /**
     * Checks if user is allowed to process process instance
     * 
     * @param string $instanceId Process instance ID
     * @param string $userId User ID
     */
    public function canUserProcessInstance(string $instanceId, string $userId): bool {
        $instance = $this->processInstanceManager->getProcessInstanceById($instanceId);

        $currentOfficerId = $instance->currentOfficerId;
        $currentOfficerType = $instance->currentOfficerType;

        if($currentOfficerType == ProcessInstanceOfficerTypes::USER) {
            if($currentOfficerId == $userId) {
                return true;
            }
        } else if($currentOfficerType == ProcessInstanceOfficerTypes::GROUP) {
            $groups = $this->groupManager->getGroupsForUser($userId);

            if(in_array($currentOfficerId, $groups)) {
                return true;
            }
        }

        return false;
    }
}

?>