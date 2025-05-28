<?php

namespace App\Authorizators;

use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Constants\Container\SystemGroups;
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

    /**
     * Checks if user can view process instance workflow history
     * 
     * There are 3 conditions and one of the must be met:
     * - User must be the current officer
     * - User must have appeared in workflow
     * - User will appear in workflow
     * 
     * @param string $instanceId Process instance ID
     * @param string $userId User ID
     */
    public function canUserViewProcessInstanceWorkflowHistory(string $instanceId, string $userId): bool {
        $instance = $this->processInstanceManager->getProcessInstanceById($instanceId);
        $instanceData = unserialize($instance->data);

        // is current officer
        $isCurrentOfficer = $this->canUserProcessInstance($instanceId, $userId);

        // has appeared in workflow
        $hasAppearedInWorkflow = true;

        if(array_key_exists('workflowHistory', $instanceData)) {
            $workflowHistory = $instanceData['workflowHistory'];

            foreach($workflowHistory as $wh) {
                if(!array_key_exists($userId, $wh)) {
                    $hasAppearedInWorkflow = false;
                }
            }
        }

        // will appear in workflow
        $willAppearInWorkflow = false;

        $process = $this->processManager->getProcessById($instance->processId);

        $definition = json_decode(base64_decode($process->definition), true);

        $forms = $definition['forms'];

        $workflow = [];
        foreach($forms as $form) {
            $workflow[] = $form['actor'];
        }

        if(!array_key_exists('workflowIndex', $instanceData)) {
            if($this->groupManager->isUserMemberOfGroupTitle($userId, SystemGroups::ADMINISTRATORS)) {
                return true;
            } else {
                return false;
            }
        }

        $currentIndex = $instanceData['workflowIndex'];

        if(($currentIndex + 1) < count($workflow)) {
            for($i = $currentIndex; $i < count($workflow); $i++) {
                [$officer, $type] = $this->processInstanceManager->evaluateNextProcessInstanceOfficer($workflow, $userId, $i);
    
                if($officer === null && $type === null) {
                    // no next workflow
                } else {
                    if($type == ProcessInstanceOfficerTypes::GROUP) {
                        $groups = $this->groupManager->getGroupsForUser($userId);
    
                        if(in_array($officer, $groups)) {
                            $willAppearInWorkflow = true;
                        }
                    } else if($type == ProcessInstanceOfficerTypes::USER) {
                        if($officer == $userId) {
                            $willAppearInWorkflow = true;
                        }
                    }
                }
            }
        } else {
            $willAppearInWorkflow = true;
        }

        return ($isCurrentOfficer || $hasAppearedInWorkflow || $willAppearInWorkflow);
    }
}

?>