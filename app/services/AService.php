<?php

namespace App\Services;

use App\Constants\ContainerStatus;
use App\Core\Application;
use App\Core\Caching\CacheFactory;
use App\Core\Container;
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
    protected Application $app;

    /**
     * Class constructor
     * 
     * @param string $serviceName Service name
     * @param Application $app Application instance
     */
    protected function __construct(string $serviceName, Application $app) {
        $this->serviceName = $serviceName;
        $this->app = $app;
        $this->logger = $this->app->logger;
        $this->serviceManager = $this->app->serviceManager;
        
        $this->cacheFactory = new CacheFactory();
    }

    /**
     * Logs information about the start of the service
     */
    protected function serviceStart(): bool {
        $this->serviceManager->startService($this->serviceName);
        return $this->logInfo('Service ' . $this->serviceName . ' started.');
    }

    /**
     * Logs information about the end of the service
     */
    protected function serviceStop(?Exception $e = null, array $args = []): bool {
        $this->serviceManager->stopService($this->serviceName, $e, $args);
        return $this->logInfo('Service ' . $this->serviceName . ' ended.');
    }

    /**
     * Logs an information
     * 
     * @param string $text Text to log
     */
    protected function logInfo(string $text): bool {
        return $this->logger->serviceInfo($text, $this->serviceName);
    }

    /**
     * Logs an error
     * 
     * @param string $text Text to log
     */
    protected function logError(string $text): bool {
        return $this->logger->serviceError($text, $this->serviceName);
    }

    /**
     * Starts slave service for all existing containers and returns the overall result
     * 
     * @param ContainerManager $containerManager ContainerManager instance
     * @param string $serviceScriptPath Service script path
     * @param array $params Custom parameters - container ID is implicitly passed as fisrt argument
     */
    protected function startSlaveServiceForAllContainers(ContainerManager $containerManager, string $serviceScriptPath, array $params = []): bool {
        $containerIds = $this->getContainerIds($containerManager);

        $this->logInfo(sprintf('Found %d containers.', count($containerIds)));

        $overallResult = true;

        foreach($containerIds as $containerId) {
            $result = $this->serviceManager->runService($serviceScriptPath, array_merge([$containerId], $params));

            if($result) {
                $this->logInfo('Slave started.');
            } else {
                if($overallResult) {
                    $overallResult = false;
                }
                $this->logError('Could not start slave.');
            }
        }

        return $overallResult;
    }

    /**
     * Returns all created container IDs
     * 
     * @param ContainerManager $containerManager ContainerManager instance
     */
    private function getContainerIds(ContainerManager $containerManager): array {
        $qb = $containerManager->containerRepository->composeQueryForContainers();
        $qb->andWhere($qb->getColumnInValues('status', [ContainerStatus::NOT_RUNNING, ContainerStatus::RUNNING]))
            ->execute();

        $containerIds = [];
        while($row = $qb->fetchAssoc()) {
            $containerIds[] = $row['containerId'];
        }

        return $containerIds;
    }

    /**
     * Saves exception to file
     * 
     * @param Exception $e Exception
     */
    protected function saveExceptionToFile(Exception $e): bool {
        if(FileManager::folderExists(LOG_DIR)) {
            return ExceptionHelper::saveExceptionToFile($e, HashManager::createHash(8, false));
        }

        return false;
    }

    /**
     * Returns a new container instance
     * 
     * @param string $containerId Container ID
     */
    protected function getContainerInstance(string $containerId): Container {
        return new Container($this->app, $containerId);
    }
}

?>