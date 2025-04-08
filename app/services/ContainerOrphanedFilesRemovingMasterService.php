<?php

namespace App\Services;

use App\Constants\ContainerStatus;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Managers\ContainerManager;
use Exception;

class ContainerOrphanedFilesRemovingMasterService extends AService {
    private ContainerManager $containerManager;

    public function __construct(Logger $logger, ServiceManager $serviceManager, ContainerManager $containerManager) {
        parent::__construct('ContainerOrphanedFilesRemovingMaster', $logger, $serviceManager);

        $this->containerManager = $containerManager;
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop($e);
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $this->startSlaveServiceForAllContainers($this->containerManager, 'cofrs_slave.php');
    }
}

?>