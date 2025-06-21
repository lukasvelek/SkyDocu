<?php

namespace App\Managers\Container;

use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Constants\Container\ProcessInstanceStatus;
use App\Constants\Container\SystemGroups;
use App\Core\DB\DatabaseRow;
use App\Entities\ProcessInstanceDataEntity;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Managers\UserManager;
use App\Repositories\Container\ProcessInstanceRepository;

/**
 * ProcessInstanceManager contains high-level database operations for process instances
 * 
 * @author Lukas Velek
 */
class ProcessInstanceManager extends AManager {
    public ProcessInstanceRepository $processInstanceRepository;
    private GroupManager $groupManager;
    private UserManager $userManager;

    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        ProcessInstanceRepository $processInstanceRepository,
        GroupManager $groupManager,
        UserManager $userManager
    ) {
        parent::__construct($logger, $entityManager);

        $this->processInstanceRepository = $processInstanceRepository;
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
    }

    /**
     * Generates unique process instance ID
     */
    public function generateUniqueInstanceId(): ?string {
        return $this->createId(EntityManager::C_PROCESS_INSTANCES);
    }

    /**
     * Starts a new process instance with data in an array and returns instance ID
     * 
     * @param ?string $instanceId Instance ID
     * @param array $data Process data
     */
    public function startNewInstanceFromArray(?string $instanceId, array $data): string {
        if($instanceId === null) {
            $instanceId = $this->generateUniqueInstanceId();
        }

        $data['instanceId'] = $instanceId;

        if(!$this->processInstanceRepository->insertNewProcessInstance($data)) {
            throw new GeneralException('Database error.');
        }

        return $instanceId;
    }

    /**
     * Updates process instance
     * 
     * @param string $instanceId Instance ID
     * @param array $data Process instance data
     */
    public function updateInstance(string $instanceId, array $data) {
        if(!array_key_exists('dateModified', $data)) {
            $data['dateModified'] = date('Y-m-d H:i:s');
        }

        if(!$this->processInstanceRepository->updateProcessInstance($instanceId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Evaluates next process instance officer for given process workflow
     * 
     * @param DatabaseRow $instance Instance
     * @param array $workflow Process workflow
     * @param string $currentUserId Current user ID
     * @param int $index Workflow index
     */
    public function evaluateNextProcessInstanceOfficer(DatabaseRow $instance, array $workflow, string $currentUserId, int $index): array {
        if(($index + 1) > count($workflow)) {
            return [null, null];
        }

        $name = $workflow[$index];

        $result = null;
        $type = null;
        switch($name) {
            case '$CURRENT_USER$':
                $result = $currentUserId;
                $type = ProcessInstanceOfficerTypes::USER;
                break;

            case '$ACCOUNTANTS$':
                $group = $this->groupManager->getGroupByTitle(SystemGroups::ACCOUNTANTS);
                $result = $group->groupId;
                $type = ProcessInstanceOfficerTypes::GROUP;
                break;

            case '$ARCHIVISTS$':
                $group = $this->groupManager->getGroupByTitle(SystemGroups::ARCHIVISTS);
                $result = $group->groupId;
                $type = ProcessInstanceOfficerTypes::GROUP;
                break;

            case '$CURRENT_USER_SUPERIOR$':
                $user = $this->userManager->getUserById($currentUserId);
                if($user->getSuperiorUserId() === null) {
                    $result = $currentUserId;
                    $type = ProcessInstanceOfficerTypes::USER;
                } else {
                    $result = $user->getSuperiorUserId();
                    $type = ProcessInstanceOfficerTypes::USER;
                }
                break;

            case '$ADMINISTRATORS$':
                $group = $this->groupManager->getGroupByTitle(SystemGroups::ADMINISTRATORS);
                $result = $group->groupId;
                $type = ProcessInstanceOfficerTypes::GROUP;
                break;

            case '$INSTANCE_AUTHOR$':
                $instanceData = ProcessInstanceDataEntity::createFromSerializedData($instance->data);
                $result = $instanceData->getWorkflowHistory()[0]['userId'];
                $type = ProcessInstanceOfficerTypes::USER;
                break;

            case '$SERVICE_USER$':
                $result = $this->userManager->getServiceUserId();
                $type = ProcessInstanceOfficerTypes::USER;
                break;

            default:
                if(str_starts_with($name, '$UID_')) {
                    // user id
                    $result = substr($name, 4, strlen($name) - 5);
                    $type = ProcessInstanceOfficerTypes::USER;
                } else if(str_starts_with($name, '$GID_')) {
                    // group id
                    $result = substr($name, 4, strlen($name) - 5);
                    $type = ProcessInstanceOfficerTypes::GROUP;
                } else {
                    throw new GeneralException('Given workflow entity does not exist.');
                }
                break;
        }

        return [
            $result, // new officer
            $type // new officer type
        ];
    }

    /**
     * Returns an instance of DatabaseRow for process instance with given $instanceId
     * 
     * @param string $instanceId Process instance ID
     */
    public function getProcessInstanceById(string $instanceId): DatabaseRow {
        $instance = $this->processInstanceRepository->getProcessInstanceById($instanceId);

        if($instance === null) {
            throw new GeneralException('Process instance \'' . $instanceId . '\' does not exist.');
        }

        return DatabaseRow::createFromDbRow($instance);
    }

    /**
     * Returns the last instance of process ID
     * 
     * @param string $processId Process ID
     * @param bool $throwException True if exception should be thrown if no instance exists, or false if null should be returned instead
     * @throws GeneralException
     */
    public function getLastInstanceForProcessId(string $processId, bool $throwException = true): ?DatabaseRow {
        $qb = $this->processInstanceRepository->commonComposeQuery();

        $qb->andWhere('processId = ?', [$processId])
            ->orderBy('dateCreated', 'DESC')
            ->limit(1)
            ->execute();

        $result = $qb->fetch();

        if($result === null) {
            if($throwException) {
                throw new GeneralException('No instance for process \'' . $processId . '\' exists.');
            } else {
                return null;
            }
        }

        return DatabaseRow::createFromDbRow($result);
    }

    /**
     * Cancels process instance
     * 
     * @param string $instanceId Process instance ID
     * @param string $userId User ID
     */
    public function cancelProcessInstance(string $instanceId, string $userId) {
        $this->changeProcessInstanceStatus($instanceId, ProcessInstanceStatus::CANCELED);
        $this->addWorkflowHistoryEntryToProcessInstance($instanceId, $userId, 'cancel');
    }

    /**
     * Accepts process instance
     * 
     * @param string $instanceId Process instance ID
     * @param string $userId User ID
     */
    public function acceptProcessInstance(string $instanceId, string $userId) {
        $this->addWorkflowHistoryEntryToProcessInstance($instanceId, $userId, 'accept');
    }

    /**
     * Rejects process instance
     * 
     * @param string $instanceId Process instance ID
     * @param string $userId User ID
     */
    public function rejectProcessInstance(string $instanceId, string $userId) {
        $this->changeProcessInstanceStatus($instanceId, ProcessInstanceStatus::FINISHED);
        $this->addWorkflowHistoryEntryToProcessInstance($instanceId, $userId, 'reject');
    }

    /**
     * Finishes process instance
     * 
     * @param string $instanceId Process instance ID
     * @param string $userId User ID
     */
    public function finishProcessInstance(string $instanceId, string $userId) {
        $this->changeProcessInstanceStatus($instanceId, ProcessInstanceStatus::FINISHED);
        $this->addWorkflowHistoryEntryToProcessInstance($instanceId, $userId, 'finish');
    }

    /**
     * Archives process instance
     * 
     * @param string $instanceId Process instance ID
     * @param string $userId User ID
     */
    public function archiveProcessInstance(string $instanceId, string $userId) {
        $this->changeProcessInstanceStatus($instanceId, ProcessInstanceStatus::ARCHIVED);
        $this->addWorkflowHistoryEntryToProcessInstance($instanceId, $userId, 'archive');
    }

    /**
     * Adds workflow history entry to process instance
     * 
     * @param string $instanceId Instance ID
     * @param string $userId User ID
     * @param string $operation Operation
     */
    private function addWorkflowHistoryEntryToProcessInstance(string $instanceId, string $userId, string $operation) {
        $instance = $this->getProcessInstanceById($instanceId);
        $instanceData = ProcessInstanceDataEntity::createFromSerializedData($instance->data);

        $instanceData->addNewWorkflowHistoryEntry($userId, $operation);

        $this->updateInstance($instanceId, [
            'data' => $instanceData->serialize()
        ]);
    }

    /**
     * Moves process instance to next officer
     * 
     * @param string $instanceId Process instance ID
     * @param string $officerId Officer ID
     * @param int $officerType Officer type
     */
    public function moveProcessInstanceToNextOfficer(string $instanceId, string $userId, string $officerId, int $officerType) {
        $instance = $this->getProcessInstanceById($instanceId);

        $instanceData = ProcessInstanceDataEntity::createFromSerializedData($instance->data);
        $instanceData->incrementWorkflowIndex();

        $this->updateInstance($instanceId, [
            'currentOfficerId' => $officerId,
            'currentOfficerType' => $officerType,
            'data' => $instanceData->serialize()
        ]);

        $this->addWorkflowHistoryEntryToProcessInstance($instanceId, $userId, 'accept');
    }

    /**
     * Changes process instance status
     * 
     * @param string $instanceId Process instance ID
     * @param int $status Status
     */
    public function changeProcessInstanceStatus(string $instanceId, int $status) {
        $this->updateInstance($instanceId, [
            'status' => $status
        ]);
    }

    /**
     * Changes process instance description
     * 
     * @param string $instanceId Instance ID
     * @param string $description Description
     */
    public function changeProcessInstanceDescription(string $instanceId, string $description) {
        $this->updateInstance($instanceId, [
            'description' => $description
        ]);
    }

    /**
     * Deletes process instance
     * 
     * @param string $instanceId Instance ID
     * @throws GeneralException
     */
    public function deleteProcessInstance(string $instanceId) {
        if(!$this->processInstanceRepository->deleteProcessInstance($instanceId)) {
            throw new GeneralException('Database error.');
        }
    }
}

?>