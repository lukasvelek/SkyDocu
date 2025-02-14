<?php

namespace App\Services;

use App\Constants\ContainerStatus;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Managers\ContainerManager;
use App\Repositories\ContainerRepository;
use Error;
use Exception;

class ContainerCreationService extends AService {
    private const BATCH_SIZE = 10;

    private ContainerManager $containerManager;
    private ContainerRepository $containerRepository;

    private array $containerStatus;

    public function __construct(Logger $logger, ServiceManager $serviceManager, ContainerManager $containerManager, ContainerRepository $containerRepository) {
        parent::__construct('ContainerCreation', $logger, $serviceManager);

        $this->containerManager = $containerManager;
        $this->containerRepository = $containerRepository;

        $this->containerStatus = [];
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception|Error $e) {
            $this->logError($e->getMessage());
            $this->serviceStop(true);

            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here

        $offset = 0;
        $cnt = 0;
        while(true) {
            if($cnt > 1000) {
                break;
            }

            $containers = $this->getContainerIdsWaitingForCreation($offset);

            $this->logInfo('Found ' . count($containers) . ' to be created.');

            if(empty($containers)) {
                break;
            }

            foreach($containers as $containerId) {
                $this->logInfo('Starting creation of container ID \'' . $containerId . '\'.');

                try {
                    $this->containerManager->changeContainerStatus($containerId, ContainerStatus::IS_BEING_CREATED, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. Container is being created.');
                    $this->containerManager->changeContainerCreationStatus($containerId, 0, null);
                    $this->logInfo('Changed container status to \'' . ContainerStatus::toString(ContainerStatus::IS_BEING_CREATED) . '\'.');
                    
                    $this->containerRepository->beginTransaction(__METHOD__);

                    $this->logInfo('Creating container');
                    $this->processContainerCreation($containerId);
                    $this->logInfo('Container created.');

                    $this->containerRepository->commit($this->serviceManager->getServiceUserId(), __METHOD__);

                    $this->containerManager->changeContainerStatus($containerId, ContainerStatus::RUNNING, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. Container is created and running.');
                    $this->containerManager->changeContainerCreationStatus($containerId, 100, null);
                    $this->logInfo('Changed container status to \'' . ContainerStatus::toString(ContainerStatus::RUNNING) . '\'.');
                } catch(AException|Exception|Error $e) {
                    $this->containerRepository->rollback(__METHOD__);

                    $this->logError($e->getMessage());

                    $this->containerManager->changeContainerStatus($containerId, ContainerStatus::ERROR_DURING_CREATION, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. An error occured during container creation.');
                    $this->containerManager->changeContainerCreationStatus($containerId, 0, null);
                    $this->logInfo('Changed container status to \'' . ContainerStatus::toString(ContainerStatus::ERROR_DURING_CREATION) . '\'.');
                }
                
                $this->logInfo('Creation of container ID \'' . $containerId . '\' ended.');
            }

            $offset += self::BATCH_SIZE;
            $cnt++;
        }
    }

    private function getContainerIdsWaitingForCreation(int $offset) {
        $qb = $this->containerRepository->composeQueryForContainersAwaitingCreation();
        $qb->limit(self::BATCH_SIZE);

        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $containers = [];
        while($row = $qb->fetchAssoc()) {
            $id = $row['containerId'];
            $containers[] = $id;
            $this->containerStatus[$id] = $row['statusId'];
        }

        return $containers;
    }

    private function processContainerCreation(string $containerId) {
        try {
            $this->containerManager->createNewContainerAsync($containerId);
        } catch(AException $e) {
            throw $e;
        }
    }
}

?>