<?php

namespace App\Services;

use App\Constants\ContainerStatus;
use App\Core\Application;
use App\Core\Caching\CacheNames;
use App\Core\Container;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;
use App\Logger\Logger;
use App\Managers\Container\ProcessManager as ContainerProcessManager;
use App\Managers\ContainerManager;
use App\Managers\ProcessManager;
use App\Repositories\ContainerRepository;
use Error;
use Exception;

class ContainerCreationSlaveService extends AService {
    private string $containerId;

    private ContainerManager $containerManager;
    private ContainerRepository $containerRepository;
    private ProcessManager $processManager;
    private Application $app;
    private ContainerProcessManager $containerProcessManager;

    public function __construct(Logger $logger, ServiceManager $serviceManager, ContainerManager $containerManager, ContainerRepository $containerRepository, ProcessManager $processManager, Application $app) {
        parent::__construct('ContainerCreationSlave', $logger, $serviceManager);

        $this->containerManager = $containerManager;
        $this->containerRepository = $containerRepository;
        $this->processManager = $processManager;
        $this->app = $app;
    }

    public function run() {
        global $argv;
        $_argv = $argv;
        unset($_argv[0]);

        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop(null, $_argv);
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop($e, $_argv);
            
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
            $this->logError(var_export($e, true));
            $this->saveExceptionToFile($e);

            $this->containerManager->changeContainerStatus($this->containerId, ContainerStatus::ERROR_DURING_CREATION, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. An error occured during container creation.');
            $this->containerManager->changeContainerCreationStatus($this->containerId, 0, null);
            $this->logInfo('Changed container status to \'' . ContainerStatus::toString(ContainerStatus::ERROR_DURING_CREATION) . '\'.');
        }
    }

    private function processContainerCreation() {
        try {
            $this->containerManager->createNewContainerAsync($this->containerId);
            $this->insertProcesses();
        } catch(AException $e) {
            throw $e;
        }
    }

    private function insertProcesses() {
        $qb = $this->processManager->processRepository->composeQueryForProcessesInDistribution();
        $qb->execute();

        $insertProcesses = [];
        $insertMetadata = [];
        while($row = $qb->fetchAssoc()) {
            $metadataDefinition = $row['metadataDefinition'];

            if($metadataDefinition !== null) {
                $metadataDefinition = json_decode(base64_decode($metadataDefinition), true);

                foreach($metadataDefinition['metadata'] as $meta) {
                    $insertMetadata[] = [
                        'title' => $meta['name'],
                        'guiTitle' => $meta['label'],
                        'type' => $meta['type'],
                        'defaultValue' => $meta['defaultValue'],
                        'isSystem' => 1,
                        'isRequired' => 1,
                        'uniqueProcessId' => $row['uniqueProcessId']
                    ];
                }
            }

            $insertProcesses[] = [
                'processId' => $row['processId'],
                'uniqueProcessId' => $row['uniqueProcessId'],
                'title' => $row['title'],
                'description' => $row['description'],
                'definition' => $row['definition'],
                'userId' => $row['userId'],
                'status' => 1
            ];
        }

        $container = new Container($this->app, $this->containerId);

        foreach($insertProcesses as $data) {
            $container->processManager->insertNewProcessFromDataArray($data);
        }

        foreach($insertMetadata as $data) {
            $container->processMetadataManager->addNewMetadata($data);
        }
    }
}

?>