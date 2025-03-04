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
        parent::__construct('ContainerOrpahedFilesRemovingMaster', $logger, $serviceManager);

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
        $containerIds = $this->getContainers();

        $this->logInfo(sprintf('Found %d containers.', count($containerIds)));

        foreach($containerIds as $containerId) {
            $result = $this->serviceManager->runService('cofrs_slave.php', [$containerId]);

            if($result) {
                $this->logInfo('Slave started.');
            } else {
                $this->logError('Could not start slave.');
            }
        }
    }

    private function getContainers() {
        $qb = $this->containerManager->containerRepository->composeQueryForContainers();
        $qb->andWhere($qb->getColumnInValues('status', [ContainerStatus::NOT_RUNNING, ContainerStatus::RUNNING]))
            ->execute();

        $containerIds = [];
        while($row = $qb->fetchAssoc()) {
            $containerIds[] = $row['containerId'];
        }
        
        return $containerIds;
    }
}

?>