<?php

namespace App\Managers\Container;

use App\Constants\Container\ProcessesGridSystemMetadata;
use App\Constants\Container\ProcessStatus;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Helpers\ProcessHelper;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ProcessRepository;

class ProcessManager extends AManager {
    public ProcessRepository $pr;
    private GroupManager $gm;

    private array $mProcessesCache;
    
    public function __construct(ProcessRepository $pr, GroupManager $gm) {
        $this->pr = $pr;
        $this->gm = $gm;

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

        if($documentId !== null) {
            $data['documentId'] = $documentId;
        }

        if(!$this->pr->insertNewProcess($processId, $data)) {
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

        if(!$this->pr->updateProcess($processId, $data)) {
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

        if(!$this->pr->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID, $process->currentOfficerUserId, $newOfficer);
    }

    public function cancelProcess(string $processId, string $reason, string $userId) {
        $process = $this->getProcessById($processId);

        $data = [
            'status' => ProcessStatus::CANCELED
        ];

        if(!$this->pr->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->insertNewProcessComment($processId, $userId, $reason);
        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::STATUS, $process->status, ProcessStatus::CANCELED);
    }

    public function finishProcess(string $processId, string $userId) {
        $process = $this->getProcessById($processId);
        
        $data = [
            'status' => ProcessStatus::FINISHED,
            'currentOfficerUserId' => null
        ];

        if(!$this->pr->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }

        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::STATUS, $process->status, $data['status']);
        $this->insertProcessMetadataHistory($processId, $userId, ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID, $process->currentOfficerUserId, null);
    }

    public function isDocumentInProcess(string $documentId) {
        $processes = $this->pr->getProcessesForDocument($documentId);

        return !empty($processes);
    }

    public function areDocumentsInProcesses(array $documentIds) {
        $tmp = $this->pr->getActiveProcessCountForDocuments($documentIds);

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
            $row = $this->pr->getProcessById($processId);

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
            $this->pr->beginTransaction(__METHOD__);
        
            $processId = $this->startProcess($documentId, $type, $userId, $currentOfficerId, $workflowUserIds);
            $this->finishProcess($processId, $userId);

            $this->pr->commit($userId, __METHOD__);
        } catch(AException $e) {
            $this->pr->rollback(__METHOD__);

            $result = false;
        }

        return $result;
    }

    public function insertNewProcessComment(string $processId, string $authorId, string $text) {
        $commentId = $this->createId(EntityManager::C_PROCESS_COMMENTS);

        if($commentId === null) {
            throw new GeneralException('Database error.');
        }

        if(!$this->pr->insertNewProcessComment($commentId, $processId, $authorId, $text)) {
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

        if(!$this->pr->insertNewProcessHistoryEntry($entryId, $data)) {
            throw new GeneralException('Database error.');
        }
    }
}

?>