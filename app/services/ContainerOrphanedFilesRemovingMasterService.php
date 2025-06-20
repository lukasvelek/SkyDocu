<?php

namespace App\Services;

use App\Core\Application;
use App\Exceptions\AException;
use Exception;

class ContainerOrphanedFilesRemovingMasterService extends AService {
    public function __construct(Application $app) {
        parent::__construct('ContainerOrphanedFilesRemovingMaster', $app);
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
        $this->startSlaveServiceForAllContainers($this->app->containerManager, 'cofrs_slave.php');
    }
}

?>