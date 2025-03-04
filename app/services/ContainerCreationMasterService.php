<?php

namespace App\Services;

use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Repositories\ContainerRepository;
use Exception;

class ContainerCreationMasterService extends AService {
    private ContainerRepository $containerRepository;

    public function __construct(Logger $logger, ServiceManager $serviceManager, ContainerRepository $containerRepository) {
        parent::__construct('ContainerCreationMaster', $logger, $serviceManager);

        $this->containerRepository = $containerRepository;
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop(true);
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $qb = $this->containerRepository->composeQueryForContainersAwaitingCreation();
        $qb->execute();

        while($row = $qb->fetchAssoc()) {
            $containerId = $row['containerId'];
            $this->logInfo('Found container ID \'' . $containerId . '\' that is awaiting creation. Starting slave...');

            $result = $this->serviceManager->runService('container_creation_slave.php', [$containerId]);

            if($result) {
                $this->logInfo('Slave started.');
            } else {
                $this->logError('Could not start slave.');
            }
        }
    }
}

?>