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

    private ContainerManager $cm;
    private ContainerRepository $cr;

    private array $containerStatus;

    public function __construct(Logger $logger, ServiceManager $serviceManager, ContainerManager $cm, ContainerRepository $cr) {
        parent::__construct('ContainerCreation', $logger, $serviceManager);

        $this->cm = $cm;
        $this->cr = $cr;

        $this->containerStatus = [];
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception|Error $e) {
            $this->logError($e->getMessage());
            $this->serviceStop();

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

            if(empty($containers)) {
                break;
            }

            foreach($containers as $containerId) {
                try {
                    $this->cm->changeContainerStatus($containerId, ContainerStatus::IS_BEING_CREATED, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. Container is being created.');
                    
                    $this->cr->beginTransaction(__METHOD__);

                    $this->processContainerCreation($containerId);

                    $this->cr->commit($this->serviceManager->getServiceUserId(), __METHOD__);

                    $this->cm->changeContainerStatus($containerId, ContainerStatus::NOT_RUNNING, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. Container is created and not running.');
                } catch(AException $e) {
                    $this->cr->rollback(__METHOD__);

                    $this->cm->changeContainerStatus($containerId, ContainerStatus::NEW, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. An error occured during container creation.');
                }
            }

            $offset += self::BATCH_SIZE;
            $cnt++;
        }
    }

    private function getContainerIdsWaitingForCreation(int $offset) {
        $qb = $this->cr->composeQueryForContainersAwaitingCreation();
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
            $this->cm->createNewContainerAsync($containerId);
        } catch(AException $e) {
            throw $e;
        }
    }
}

?>