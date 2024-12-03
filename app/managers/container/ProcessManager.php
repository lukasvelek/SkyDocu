<?php

namespace App\Managers\Container;

use App\Managers\AManager;
use App\Repositories\Container\ProcessRepository;

class ProcessManager extends AManager {
    private ProcessRepository $pr;
    private GroupManager $gm;
    
    public function __construct(ProcessRepository $pr, GroupManager $gm) {
        $this->pr = $pr;
        $this->gm = $gm;
    }

    public function startProcess(string $documentId, string $type, string $userId) {

    }

    public function nextWorkflowProcess(string $processId, string $userId) {

    }

    public function cancelProcess(string $processId, string $reason, string $userId) {

    }

    public function finishProcess(string $processId, string $userId) {
        
    }
}

?>