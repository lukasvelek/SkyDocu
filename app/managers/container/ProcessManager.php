<?php

namespace App\Managers\Container;

use App\Constants\Container\ProcessesGridSystemMetadata;
use App\Constants\Container\ProcessInstanceStatus;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Helpers\ProcessHelper;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Managers\UserAbsenceManager;
use App\Managers\UserSubstituteManager;
use App\Repositories\Container\ProcessRepository;

/**
 * ProcessManager is responsible for managing processes
 * 
 * @author Lukas Velek
 */
class ProcessManager extends AManager {
    public ProcessRepository $processRepository;
    private GroupManager $groupManager;
    private UserSubstituteManager $userSubstituteManager;
    private UserAbsenceManager $userAbsenceManager;

    private array $mProcessesCache;
    
    /**
     * Class constructor
     * 
     * @param Logger $logger
     * @param EntityManager $entityManager
     * @param ProcessRepository $processRepository
     * @param GroupManager $groupManager
     * @param UserSubstituteManager $userSubstituteManager
     * @param UserAbsenceManager $userAbsenceManager
     */
    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        ProcessRepository $processRepository,
        GroupManager $groupManager,
        UserSubstituteManager $userSubstituteManager,
        UserAbsenceManager $userAbsenceManager
    ) {
        parent::__construct($logger, $entityManager);
        
        $this->processRepository = $processRepository;
        $this->groupManager = $groupManager;
        $this->userSubstituteManager = $userSubstituteManager;
        $this->userAbsenceManager = $userAbsenceManager;

        $this->mProcessesCache = [];
    }

    /**
     * Starts given process
     * 
     * @param string $type Proces type
     * @param string $userId Process author user ID
     * @param string $currentOfficerUserId Process current officer user ID
     * @param array $workflowUserIds Array of user IDs that are in the workflow
     * @return string Process ID
     */
    public function startProcess(string $type, string $userId, string $currentOfficerId, array $workflowUserIds): string {
        $processId = $this->createId(EntityManager::C_PROCESSES);

        if($processId === null) {
            throw new GeneralException('Database error.');
        }

        $workflowConverted = ProcessHelper::convertWorkflowToDb($workflowUserIds);

        $data = [
            'type' => $type,
            'authorUserId' => $userId,
            'currentOfficerUserId' => $currentOfficerId,
            'workflowUserIds' => $workflowConverted
        ];

        if($this->userAbsenceManager->isUserAbsent($currentOfficerId)) {
            $substitute = $this->userSubstituteManager->getUserOrTheirSubstitute($currentOfficerId);

            if($substitute != $currentOfficerId) {
                $data['currentOfficerSubstituteUserId'] = $substitute;
            }
        }

        if(!$this->processRepository->insertNewProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        return $processId;
    }

    /**
     * Moves the process to the previous workflow user
     * 
     * @param string $processId Process ID
     * @param string $userId User ID
     */
    public function previousWorkflowProcess(string $processId, string $userId) {
        $process = $this->getProcessById($processId);

        $workflowUsers = ProcessHelper::convertWorkflowFromDb($process);

        $i = 0;
        foreach($workflowUsers as $workflowUserId) {
            if($workflowUserId == $process->currentOfficerUserId) {
                break;
            }

            $i++;
        }

        $newOfficer = $workflowUsers[$i - 2];

        $data = [
            'currentOfficerUserId' => $newOfficer
        ];

        if($this->userAbsenceManager->isUserAbsent($newOfficer)) {
            $substitute = $this->userSubstituteManager->getUserOrTheirSubstitute($newOfficer);

            if($substitute != $newOfficer) {
                $data['currentOfficerSubstituteUserId'] = $substitute;
            }
        } else {
            $data['currentOfficerSubstituteUserId'] = null;
        }

        if(!$this->processRepository->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID, $process->currentOfficerUserId, $newOfficer);
    }

    /**
     * Moves the process to the next workflow user
     * 
     * @param string $processId Process ID
     * @param string $userId User ID
     */
    public function nextWorkflowProcess(string $processId, string $userId) {
        $process = $this->getProcessById($processId);

        $workflowUsers = ProcessHelper::convertWorkflowFromDb($process);

        $i = 0;
        foreach($workflowUsers as $workflowUserId) {
            if($workflowUserId == $process->currentOfficerUserId) {
                break;
            }

            $i++;
        }

        $newOfficer = $workflowUsers[$i];

        $data = [
            'currentOfficerUserId' => $newOfficer
        ];

        if($this->userAbsenceManager->isUserAbsent($newOfficer)) {
            $substitute = $this->userSubstituteManager->getUserOrTheirSubstitute($newOfficer);

            if($substitute != $newOfficer) {
                $data['currentOfficerSubstituteUserId'] = $substitute;
            }
        } else {
            $data['currentOfficerSubstituteUserId'] = null;
        }

        if(!$this->processRepository->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID, $process->currentOfficerUserId, $newOfficer);
    }

    /**
     * Cancels given process
     * 
     * @param string $processId Process ID
     * @param string $reason Reason of process canceling
     * @param string $userId User ID
     */
    public function cancelProcess(string $processId, string $reason, string $userId) {
        $process = $this->getProcessById($processId);

        $data = [
            'status' => ProcessInstanceStatus::CANCELED
        ];

        if(!$this->processRepository->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->insertNewProcessComment($processId, $userId, $reason);
        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::STATUS, $process->status, ProcessInstanceStatus::CANCELED);
    }

    /**
     * Finishes given process
     * 
     * @param string $processId Process ID
     * @param string $userId User ID
     */
    public function finishProcess(string $processId, string $userId) {
        $process = $this->getProcessById($processId);
        
        $data = [
            'status' => ProcessInstanceStatus::FINISHED,
            'currentOfficerUserId' => null,
            'currentOfficerSubstituteUserId' => null
        ];

        if(!$this->processRepository->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::STATUS, $process->status, $data['status']);
        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID, $process->currentOfficerUserId, null);
    }

    /**
     * Checks if given document is in a process
     * 
     * @param string $documentId Document ID
     * @return bool True if document is in a process or false if not
     */
    public function isDocumentInProcess(string $documentId): bool {
        $processes = $this->processRepository->getProcessesForDocument($documentId);

        return !empty($processes);
    }

    /**
     * Checks if given documents are in processes. Returns array of all document IDs that are in processes.
     * 
     * @param array $documentIds Array of document IDs
     * @return array Array of document IDs that are in processes
     */
    public function areDocumentsInProcesses(array $documentIds): array {
        $tmp = $this->processRepository->getActiveProcessCountForDocuments($documentIds);

        $result = [];
        foreach($tmp as $documentId => $count) {
            if($count > 0) {
                $result[] = $documentId;
            }
        }

        return $result;
    }

    /**
     * Returns process by its ID
     * 
     * @param string $processId Process ID
     * @return DatabaseRow Process database row
     */
    public function getProcessById(string $processId) {
        if(!array_key_exists($processId, $this->mProcessesCache)) {
            $row = $this->processRepository->getProcessById($processId);

            if($row === null) {
                throw new NonExistingEntityException('Process does not exist.', null, false);
            }

            $row = DatabaseRow::createFromDbRow($row);

            $this->mProcessesCache[$processId] = $row;
        }

        return $this->mProcessesCache[$processId];
    }

    /**
     * Inserts a new process comment
     * 
     * @param string $processId Process ID
     * @param string $authorId Author ID
     * @param string $text Comment text
     */
    public function insertNewProcessComment(string $processId, string $authorId, string $text) {
        $commentId = $this->createId(EntityManager::C_PROCESS_COMMENTS);

        if($commentId === null) {
            throw new GeneralException('Database error.');
        }

        if(!$this->processRepository->insertNewProcessComment($commentId, $processId, $authorId, $text)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Deletes a process comment
     * 
     * @param string $processId Process ID
     * @param string $commentId Comment ID
     */
    public function deleteProcessComment(string $processId, string $commentId) {
        if(!$this->processRepository->deleteProcessCommentById($commentId)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Inserts process metadata history entry
     * 
     * @param string $processId Process ID
     * @param string $userId User ID
     * @param string $metadataName Metadata name
     * @param mixed $oldValue Old metadata value
     * @param mixed $newValue New metadata value
     */
    public function insertProcessMetadataHistory(string $processId, string $userId, string $metadataName, mixed $oldValue, mixed $newValue) {
        $entryId = $this->createId(EntityManager::C_PROCESS_METADATA_HISTORY);

        if($entryId === null) {
            throw new GeneralException('Database error.');
        }

        $data = [
            'processId' => $processId,
            'userId' => $userId,
            'metadataName' => $metadataName
        ];

        if($oldValue !== null) {
            $data['oldValue'] = $oldValue;
        }
        if($newValue !== null) {
            $data['newValue'] = $newValue;
        }

        if(!$this->processRepository->insertNewProcessHistoryEntry($entryId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Deletes given process type
     * 
     * @param string $typeKey Type key
     */
    public function deleteProcessType(string $typeKey) {
        $qb = $this->processRepository->commonComposeQuery(false);
        $qb->andWhere('type = ?', [$typeKey])
            ->execute();

        $processIds = [];
        while($row = $qb->fetchAssoc()) {
            $processIds[] = $row['processId'];
        }

        foreach($processIds as $processId) {
            if(!$this->processRepository->deleteProcessDataById($processId)) {
                throw new GeneralException('Database error.');
            }
            if(!$this->processRepository->deleteProcessCommentsForProcessId($processId)) {
                throw new GeneralException('Database error.');
            }
            if(!$this->processRepository->deleteProcessById($processId)) {
                throw new GeneralException('Database error.');
            }
        }

        if(!$this->processRepository->deleteProcessTypeByTypeKey($typeKey)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Inserts new process type
     * 
     * @param string $typeKey Type key
     * @param string $title GUI title
     * @param string $description Description
     * @param bool $enabled True if enabled
     */
    public function insertNewProcessType(string $typeKey, string $title, string $description, bool $enabled = true) {
        $typeId = $this->createId(EntityManager::C_PROCESS_TYPES);

        if(!$this->processRepository->insertNewProcessType($typeId, $typeKey, $title, $description, $enabled)) {
            throw new GeneralException('Database error.');
        }
    }
}

?>