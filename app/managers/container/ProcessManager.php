<?php

namespace App\Managers\Container;

use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Managers\AManager;
use App\Repositories\Container\ProcessRepository;

class ProcessManager extends AManager {
    public ProcessRepository $pr;
    private GroupManager $gm;

    private Cache $processTypes;
    
    public function __construct(ProcessRepository $pr, GroupManager $gm) {
        $this->pr = $pr;
        $this->gm = $gm;

        $this->processTypes = $this->cacheFactory->getCache(CacheNames::PROCESS_TYPES);
    }

    public function startProcess(string $documentId, string $type, string $userId) {

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
}

?>