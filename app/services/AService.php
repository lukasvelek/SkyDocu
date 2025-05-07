<?php

namespace App\Services;

use App\Constants\ContainerStatus;
use App\Core\Caching\CacheFactory;
use App\Core\FileManager;
use App\Core\HashManager;
use App\Core\ServiceManager;
use App\Helpers\ExceptionHelper;
use App\Logger\Logger;
use App\Managers\ContainerManager;
use Exception;

/**
 * Common class for all services
 * 
 * @author Lukas Velek
 */
abstract class AService implements IRunnable {
    protected Logger $logger;
    protected ServiceManager $serviceManager;
    protected string $serviceName;
    protected CacheFactory $cacheFactory;

    /**
     * Class constructor
     * 
     * @param string $serviceName Service name
     * @param Logger $logger Logger instance
     * @param ServiceManager $serviceManager ServiceManager instance
     */
    protected function __construct(string $serviceName, Logger $logger, ServiceManager $serviceManager) {
        $this->serviceName = $serviceName;
        $this->logger = $logger;
        $this->serviceManager = $serviceManager;
        
        $this->cacheFactory = new CacheFactory();
    }

    /**
     * Logs information about the start of the service
     */
    protected function serviceStart() {
        $this->serviceManager->startService($this->serviceName);
        $this->logInfo('Service ' . $this->serviceName . ' started.');
    }

    /**
     * Logs information about the end of the service
     */
    protected function serviceStop(?Exception $e = null, array $args = []) {
        $this->serviceManager->stopService($this->serviceName, $e, $args);
        $this->logInfo('Service ' . $this->serviceName . ' ended.');
    }

    /**
     * Logs an information
     * 
     * @param string $text Text to log
     */
    protected function logInfo(string $text) {
        $this->logger->serviceInfo($text, $this->serviceName);
    }

    /**
     * Logs an error
     * 
     * @param string $text Text to log
     */
    protected function logError(string $text) {
        $this->logger->serviceError($text, $this->serviceName);
    }

    /**
     * Starts slave service for all existing containers
     * 
     * @param ContainerManager $containerManager ContainerManager instance
     * @param string $serviceScriptPath Service script path
     * @param array $params Custom parameters - container ID is implicitly passed as fisrt argument
     */
    protected function startSlaveServiceForAllContainers(ContainerManager $containerManager, string $serviceScriptPath, array $params = []) {
        $containerIds = $this->getContainerIds($containerManager);

        $this->logInfo(sprintf('Found %d containers.', count($containerIds)));

        foreach($containerIds as $containerId) {
            $result = $this->serviceManager->runService($serviceScriptPath, array_merge([$containerId], $params));

            if($result) {
                $this->logInfo('Slave started.');
            } else {
                $this->logError('Could not start slave.');
            }
        }
    }

    /**
     * Returns all created container IDs
     * 
     * @param ContainerManager $containerManager ContainerManager instance
     */
    private function getContainerIds(ContainerManager $containerManager) {
        $qb = $containerManager->containerRepository->composeQueryForContainers();
        $qb->andWhere($qb->getColumnInValues('status', [ContainerStatus::NOT_RUNNING, ContainerStatus::RUNNING]))
            ->execute();

        $containerIds = [];
        while($row = $qb->fetchAssoc()) {
            $containerIds[] = $row['containerId'];
        }

        return $containerIds;
    }

    protected function saveExceptionToFile(Exception $e) {
        if(FileManager::folderExists(LOG_DIR)) {
            ExceptionHelper::saveExceptionToFile($e, HashManager::createHash(8, false));
        }
    }
}

?>