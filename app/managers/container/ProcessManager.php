<?php

namespace App\Managers\Container;

use App\Constants\Container\ProcessesGridSystemMetadata;
use App\Constants\Container\ProcessStatus;
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

class ProcessManager extends AManager {
    public ProcessRepository $processRepository;
    private GroupManager $groupManager;
    private UserSubstituteManager $userSubstituteManager;
    private UserAbsenceManager $userAbsenceManager;

    private array $mProcessesCache;
    
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

    public function startProcess(?string $documentId, string $type, string $userId, string $currentOfficerId, array $workflowUserIds) {
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

        if($documentId !== null) {
            $data['documentId'] = $documentId;
        }

        if(!$this->processRepository->insertNewProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        return $processId;
    }

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

    public function cancelProcess(string $processId, string $reason, string $userId) {
        $process = $this->getProcessById($processId);

        $data = [
            'status' => ProcessStatus::CANCELED
        ];

        if(!$this->processRepository->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->insertNewProcessComment($processId, $userId, $reason);
        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::STATUS, $process->status, ProcessStatus::CANCELED);
    }

    public function finishProcess(string $processId, string $userId) {
        $process = $this->getProcessById($processId);
        
        $data = [
            'status' => ProcessStatus::FINISHED,
            'currentOfficerUserId' => null,
            'currentOfficerSubstituteUserId' => null
        ];

        if(!$this->processRepository->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::STATUS, $process->status, $data['status']);
        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID, $process->currentOfficerUserId, null);
    }

    public function isDocumentInProcess(string $documentId) {
        $processes = $this->processRepository->getProcessesForDocument($documentId);

        return !empty($processes);
    }

    public function areDocumentsInProcesses(array $documentIds) {
        $tmp = $this->processRepository->getActiveProcessCountForDocuments($documentIds);

        $result = [];
        foreach($tmp as $documentId => $count) {
            if($count > 0) {
                $result[] = $documentId;
            }
        }

        return $result;
    }

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

    public function saveProcess(string $documentId, string $type, string $userId, string $currentOfficerId, array $workflowUserIds) {
        $result = true;

        try {
            $this->processRepository->beginTransaction(__METHOD__);
        
            $processId = $this->startProcess($documentId, $type, $userId, $currentOfficerId, $workflowUserIds);
            $this->finishProcess($processId, $userId);

            $this->processRepository->commit($userId, __METHOD__);
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $result = false;
        }

        return $result;
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

    public function insertNewProcessType(string $typeKey, string $title, string $description, bool $enabled = true) {
        $typeId = $this->createId(EntityManager::C_PROCESS_TYPES);

        if(!$this->processRepository->insertNewProcessType($typeId, $typeKey, $title, $description, $enabled)) {
            throw new GeneralException('Database error.');
        }
    }
}

?>