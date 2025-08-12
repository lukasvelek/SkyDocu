<?php

namespace App\Authorizators;

use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Constants\Container\ProcessInstanceStatus;
use App\Constants\Container\ReportRightOperations;
use App\Constants\Container\SystemGroups;
use App\Constants\JobQueueTypes;
use App\Core\DatabaseConnection;
use App\Helpers\ArrayHelper;
use App\Logger\Logger;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessInstanceManager;
use App\Managers\Container\ProcessManager;
use App\Managers\Container\ProcessReportManager;
use App\Managers\UserManager;
use App\Repositories\JobQueueRepository;

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
    private JobQueueRepository $jobQueueRepository;
    private ProcessReportManager $processReportManager;
    
    public function __construct(DatabaseConnection $conn, Logger $logger, ProcessManager $processManager, ProcessInstanceManager $processInstanceManager, GroupManager $groupManager, UserManager $userManager, JobQueueRepository $jobQueueRepository, ProcessReportManager $processReportManager) {
        parent::__construct($conn, $logger);

        $this->processManager = $processManager;
        $this->processInstanceManager = $processInstanceManager;
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
        $this->jobQueueRepository = $jobQueueRepository;
        $this->processReportManager = $processReportManager;
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
                [$officer, $type] = $this->processInstanceManager->evaluateNextProcessInstanceOfficer($instance, $workflow, $userId, $i);
    
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

    /**
     * Checks if user can delete process instance
     * 
     * @param string $instanceId Instance ID
     * @param string $userId User ID
     */
    public function canUserDeleteProcessInstance(string $instanceId, string $userId): bool {
        $qb = $this->jobQueueRepository->commonComposeQuery();
        $qb->andWhere('type = ?', [JobQueueTypes::DELETE_CONTAINER_PROCESS_INSTANCE])
            ->andWhere('params LIKE ?', ['%"instanceId":"' . $instanceId . '"%'])
            ->execute();

        $items = [];
        while($row = $qb->fetchAssoc()) {
            $items[] = $row;
        }

        $userGroups = $this->groupManager->getGroupsForUser($userId);
        $adminGroup = $this->groupManager->getGroupByTitle(SystemGroups::ADMINISTRATORS)->groupId;
        $processSupervisorGroup = $this->groupManager->getGroupByTitle(SystemGroups::PROCESS_SUPERVISOR)->groupId;

        if(!empty($items)) {
            return false;
        }

        return in_array($adminGroup, $userGroups) || in_array($processSupervisorGroup, $userGroups);
    }

    /**
     * Checks if user can cancel process instance
     * 
     * @param string $instanceId Instance ID
     * @param string $userId User ID
     */
    public function canUserCancelProcessInstance(string $instanceId, string $userId): bool {
        $qb = $this->jobQueueRepository->commonComposeQuery();
        $qb->andWhere('type = ?', [JobQueueTypes::DELETE_CONTAINER_PROCESS_INSTANCE])
            ->andWhere('params LIKE ?', ['%"instanceId":"' . $instanceId . '"%'])
            ->execute();

        $items = [];
        while($row = $qb->fetchAssoc()) {
            $items[] = $row;
        }

        $userGroups = $this->groupManager->getGroupsForUser($userId);
        $adminGroup = $this->groupManager->getGroupByTitle(SystemGroups::ADMINISTRATORS)->groupId;
        $processSupervisorGroup = $this->groupManager->getGroupByTitle(SystemGroups::PROCESS_SUPERVISOR)->groupId;

        $instance = $this->processInstanceManager->getProcessInstanceById($instanceId);
        $isAuthor = ($instance->userId == $userId);

        if(!empty($items)) {
            return false;
        }

        if(!in_array($instance->status, [
            ProcessInstanceStatus::NEW,
            ProcessInstanceStatus::IN_PROGRESS
        ])) {
            return false;
        }

        return in_array($adminGroup, $userGroups) || in_array($processSupervisorGroup, $userGroups) || $isAuthor;
    }

    /**
     * Returns true if user can read process report
     * 
     * @param string $userId User ID
     * @param string $reportId Report ID
     */
    public function canUserReadProcessReport(string $userId, string $reportId): bool {
        return in_array(ReportRightOperations::READ, $this->internalGetUserProcessReportRights($userId, $reportId));
    }

    /**
     * Returns true if user can edit process report
     * 
     * @param string $userId User ID
     * @param string $reportId Report ID
     */
    public function canUserEditProcessReport(string $userId, string $reportId): bool {
        return in_array(ReportRightOperations::EDIT, $this->internalGetUserProcessReportRights($userId, $reportId));
    }

    /**
     * Returns true if user can delete process report
     * 
     * @param string $userId User ID
     * @param string $reportId Report ID
     */
    public function canUserDeleteProcessReport(string $userId, string $reportId): bool {
        return in_array(ReportRightOperations::DELETE, $this->internalGetUserProcessReportRights($userId, $reportId));
    }

    /**
     * Returns true if user can grant process report rights
     * 
     * @param string $userId User ID
     * @param string $reportId Report ID
     */
    public function canUserGrantProcessReport(string $userId, string $reportId): bool {
        return in_array(ReportRightOperations::GRANT, $this->internalGetUserProcessReportRights($userId, $reportId));
    }

    /**
     * Returns an array of all rights user is granted on report
     * 
     * @param string $userId User ID
     * @param string $reportId Report ID
     */
    private function internalGetUserProcessReportRights(string $userId, string $reportId): array {
        // check user rights
        $userRights = $this->processReportManager->getReportRightsForUser($reportId, $userId);

        // check user groups
        $userGroups = $this->groupManager->getGroupsForUser($userId);

        // check user groups rights
        $groupRights = [];
        foreach($userGroups as $groupId) {
            $current = $this->processReportManager->getReportRightsForGroup($reportId, $groupId);

            foreach($current as $curr) {
                if(!in_array($curr, $groupRights)) {
                    $groupRights[] = $curr;
                }
            }
        }

        // merge list
        return ArrayHelper::joinArrays($userRights, $groupRights);
    }
}

?>