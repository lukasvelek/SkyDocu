<?php

namespace App\Managers\Container;

use App\Constants\Container\SystemProcessTypes;
use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ProcessRepository;

class ProcessManager extends AManager {
    public ProcessRepository $pr;
    private GroupManager $gm;

    private Cache $processTypes;
    
    public function __construct(ProcessRepository $pr, GroupManager $gm) {
        $this->pr = $pr;
        $this->gm = $gm;
    }

    public function startup() {
        parent::startup();

        $this->processTypes = $this->cacheFactory->getCache(CacheNames::PROCESS_TYPES);
    }

    public function startProcess(string $documentId, string $type, string $userId, string $currentOfficerId, array $workflowUserIds) {
        $processId = $this->createId(EntityManager::C_PROCESSES);

        $workflowConverted = implode(';', $workflowUserIds);

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

    }

    public function cancelProcess(string $processId, string $reason, string $userId) {

    }

    public function finishProcess(string $processId, string $userId) {
        
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
}

?>