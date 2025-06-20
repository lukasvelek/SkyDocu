<?php

namespace App\Services;

use App\Core\Application;
use App\Exceptions\AException;
use Exception;

class ContainerCreationMasterService extends AService {
    public function __construct(Application $app) {
        parent::__construct('ContainerCreationMaster', $app);
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
        $this->getCount();

        $qb = $this->app->containerRepository->composeQueryForContainersAwaitingCreation();
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

    private function getCount() {
        $qb = $this->app->containerRepository->composeQueryForContainersAwaitingCreation();
        $qb->select(['COUNT(*) AS cnt'])
            ->execute();

        $this->logInfo(sprintf('Found %d containers awaiting creation.', $qb->fetch('cnt')));
    }
}

?>