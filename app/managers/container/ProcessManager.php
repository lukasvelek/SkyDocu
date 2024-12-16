<?php

namespace App\Managers\Container;

use App\Constants\Container\ProcessStatus;
use App\Constants\Container\SystemProcessTypes;
use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Helpers\ProcessHelper;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ProcessRepository;

class ProcessManager extends AManager {
    public ProcessRepository $pr;
    private GroupManager $gm;

    private Cache $processTypes;

    private array $mProcessesCache;
    
    public function __construct(ProcessRepository $pr, GroupManager $gm) {
        $this->pr = $pr;
        $this->gm = $gm;

        $this->mProcessesCache = [];
    }

    public function startup() {
        parent::startup();

        $this->processTypes = $this->cacheFactory->getCache(CacheNames::PROCESS_TYPES);
    }

    public function startProcess(string $documentId, string $type, string $userId, string $currentOfficerId, array $workflowUserIds) {
        $processId = $this->createId(EntityManager::C_PROCESSES);

        $workflowConverted = ProcessHelper::convertWorkflowToDb($workflowUserIds);

        $data = [
            'documentId' => $documentId,
            'type' => $type,
            'authorUserId' => $userId,
            'currentOfficerUserId' => $currentOfficerId,
            'workflowUserIds' => $workflowConverted
        ];

        if(!$this->pr->insertNewProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }
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
    }

    public function cancelProcess(string $processId, string $reason, string $userId) {
        $data = [
            'status' => ProcessStatus::CANCELED
        ];

        if(!$this->pr->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    public function finishProcess(string $processId, string $userId) {
        $data = [
            'status' => ProcessStatus::FINISHED
        ];

        if(!$this->pr->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    public function getProcessTypeByKey(string $key) {
        return $this->processTypes->load($key, function() use ($key) {
            $row = $this->pr->getProcessTypeByKey($key);

            return DatabaseRow::createFromDbRow($row);
        });
    }

    public function isDocumentInProcess(string $documentId) {
        $processes = $this->pr->getProcessesForDocument($documentId);

        return !empty($processes);
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
}

?>