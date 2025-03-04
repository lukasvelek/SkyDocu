<?php

namespace App\Services;

use App\Constants\ContainerStatus;
use App\Core\Caching\CacheNames;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;
use App\Logger\Logger;
use App\Managers\ContainerManager;
use App\Repositories\ContainerRepository;
use Error;
use Exception;

class ContainerCreationSlaveService extends AService {
    private string $containerId;

    private ContainerManager $containerManager;
    private ContainerRepository $containerRepository;

    public function __construct(Logger $logger, ServiceManager $serviceManager, ContainerManager $containerManager, ContainerRepository $containerRepository) {
        parent::__construct('ContainerCreationSlave', $logger, $serviceManager);

        $this->containerManager = $containerManager;
        $this->containerRepository = $containerRepository;
    }

    public function run() {
        global $argv;
        $_argv = $argv;
        unset($_argv[0]);

        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop(false, $_argv);
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop(true, $_argv);
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        global $argv;

        if(count($argv) == 1) {
            throw new ServiceException('No arguments passed.');
        }

        $this->containerId = $argv[1];

        try {
            $this->containerManager->changeContainerStatus($this->containerId, ContainerStatus::IS_BEING_CREATED, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. Container is being created.');
            $this->containerManager->changeContainerCreationStatus($this->containerId, 0, null);
            $this->logInfo('Changed container status to \'' . ContainerStatus::toString(ContainerStatus::IS_BEING_CREATED) . '\'.');

            $this->containerRepository->beginTransaction(__METHOD__);

            $this->logInfo('Creating container.');
            $this->processContainerCreation();
            $this->logInfo('Container created.');

            $this->containerRepository->commit($this->serviceManager->getServiceUserId(), __METHOD__);

            $this->containerManager->changeContainerStatus($this->containerId, ContainerStatus::NOT_RUNNING, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. Container is created and not running.');
            $this->containerManager->changeContainerCreationStatus($this->containerId, 100, null);
            $this->logInfo('Changed container status to \'' . ContainerStatus::toString(ContainerStatus::NOT_RUNNING) . '\'.');

            $this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS);
            $this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS);
            $this->cacheFactory->invalidateCacheByNamespace(CacheNames::NAVBAR_CONTAINER_SWITCH_USER_MEMBERSHIPS);
        } catch(AException|Exception|Error $e) {
            $this->containerRepository->rollback(__METHOD__);

            $this->logError($e->getMessage());

            $this->containerManager->changeContainerStatus($this->containerId, ContainerStatus::ERROR_DURING_CREATION, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. An error occured during container creation.');
            $this->containerManager->changeContainerCreationStatus($this->containerId, 0, null);
            $this->logInfo('Changed container status to \'' . ContainerStatus::toString(ContainerStatus::ERROR_DURING_CREATION) . '\'.');
        }
    }

    private function processContainerCreation() {
        try {
            $this->containerManager->createNewContainerAsync($this->containerId);
        } catch(AException $e) {
            throw $e;
        }
    }
}

?>