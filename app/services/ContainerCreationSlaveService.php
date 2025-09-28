<?php

namespace App\Services;

use App\Constants\ContainerStatus;
use App\Core\Application;
use App\Core\Caching\CacheNames;
use App\Core\Container;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;
use Error;
use Exception;

class ContainerCreationSlaveService extends AService {
    private string $containerId;

    public function __construct(Application $app) {
        parent::__construct('ContainerCreationSlave', $app);
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
            $this->app->containerManager->changeContainerStatus($this->containerId, ContainerStatus::IS_BEING_CREATED, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. Container is being created.');
            $this->app->containerManager->changeContainerCreationStatus($this->containerId, 0, null);
            $this->logInfo('Changed container status to \'' . ContainerStatus::toString(ContainerStatus::IS_BEING_CREATED) . '\'.');

            $this->app->containerRepository->beginTransaction(__METHOD__);

            $this->logInfo('Creating container.');
            $this->processContainerCreation();
            $this->logInfo('Container created.');

            $this->app->containerRepository->commit($this->serviceManager->getServiceUserId(), __METHOD__);

            $this->app->containerManager->changeContainerStatus($this->containerId, ContainerStatus::NOT_RUNNING, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. Container is created and not running.');
            $this->app->containerManager->changeContainerCreationStatus($this->containerId, 100, null);
            $this->logInfo('Changed container status to \'' . ContainerStatus::toString(ContainerStatus::NOT_RUNNING) . '\'.');

            $this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS);
            $this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS);
            $this->cacheFactory->invalidateCacheByNamespace(CacheNames::NAVBAR_CONTAINER_SWITCH_USER_MEMBERSHIPS);
        } catch(AException|Exception|Error $e) {
            $this->app->containerRepository->rollback(__METHOD__);

            $this->logError($e->getMessage());
            $this->logError(var_export($e, true));
            $this->saveExceptionToFile($e);

            $this->app->containerManager->changeContainerStatus($this->containerId, ContainerStatus::ERROR_DURING_CREATION, $this->serviceManager->getServiceUserId(), 'Status change due to background container creation. An error occured during container creation.');
            $this->app->containerManager->changeContainerCreationStatus($this->containerId, 0, null);
            $this->logInfo('Changed container status to \'' . ContainerStatus::toString(ContainerStatus::ERROR_DURING_CREATION) . '\'.');
        }
    }

    private function processContainerCreation() {
        try {
            $this->app->containerManager->createNewContainerAsync($this->containerId);
            $this->insertProcesses();
        } catch(AException $e) {
            throw $e;
        }
    }

    private function insertProcesses() {
        $qb = $this->app->processManager->processRepository->composeQueryForProcessesInDistribution();
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
                'status' => 1,
                'name' => $row['name']
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