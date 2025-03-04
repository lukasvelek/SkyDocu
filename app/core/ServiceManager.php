<?php

namespace App\Core;

use App\Constants\SystemServiceHistoryStatus;
use App\Constants\SystemServiceStatus;
use App\Exceptions\AException;
use App\Exceptions\FileDoesNotExistException;
use App\Exceptions\ServiceException;
use App\Managers\EntityManager;
use App\Repositories\SystemServicesRepository;
use App\Repositories\UserRepository;

/**
 * Service manager allows running background services
 * 
 * @author Lukas Velek
 */
class ServiceManager {
    public SystemServicesRepository $systemServicesRepository;
    private UserRepository $userRepository;
    private EntityManager $entityManager;

    /**
     * Class constructor
     * 
     * @param SystemServicesRepository $systemServicesRepository SystemServicesRepository instance
     * @param UserRepository $userRepository UserRepository instance
     * @param EntityManager $entityManager EntityManager instance
     */
    public function __construct(SystemServicesRepository $systemServicesRepository, UserRepository $userRepository, EntityManager $entityManager) {
        $this->systemServicesRepository = $systemServicesRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Starts a background PHP CLI and runs the given script
     * 
     * @param string $scriptPath Script path to be run in background
     * @param array $args Optional arguments
     * @return bool True if the script was run successfully or false if not
     */
    public function runService(string $scriptPath, array $args = []) {
        $phpExe = PHP_ABSOLUTE_DIR . 'php.exe';

        if(!FileManager::fileExists($phpExe)) {
            throw new FileDoesNotExistException($phpExe);
        }

        $serviceFile = APP_ABSOLUTE_DIR . 'services\\' . $scriptPath;

        if(!FileManager::fileExists($serviceFile)) {
            throw new FileDoesNotExistException($serviceFile);
        }

        $cmd = $phpExe . ' ' . $serviceFile;

        if(substr(php_uname(), 0, 7) == 'Windows') {
            $p = popen("start /B " . $cmd . implode(' ', $args), "w");
            if($p === false) {
                return false;
            }
            $status = pclose($p);
            if($status == -1) {
                return false;
            }
        } else {
            $status = exec($cmd . implode(' ', $args) . " > /dev/null &");
            if($status === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Updates service status to "Running"
     * 
     * @param string $serviceTitle Service name
     */
    public function startService(string $serviceTitle) {
        $serviceId = $this->getServiceId($serviceTitle);

        if(!$this->systemServicesRepository->updateService($serviceId, ['dateStarted' => date('Y-m-d H:i:s'), 'dateEnded' => NULL, 'status' => SystemServiceStatus::RUNNING])) {
            throw new ServiceException('Could not update service status.');
        }
    }

    /**
     * Updates service status to "Not running" and creates a service history entry
     * 
     * @param string $serviceTitle Service name
     * @param bool $error Finished with error?
     * @param array $args Arguments
     * @throws ServiceException
     */
    public function stopService(string $serviceTitle, bool $error, array $args = []) {
        $serviceId = $this->getServiceId($serviceTitle);

        if(!$this->systemServicesRepository->updateService($serviceId, ['dateEnded' => date('Y-m-d H:i:s'), 'status' => SystemServiceStatus::NOT_RUNNING])) {
            throw new ServiceException('Could not update service status.');
        }

        try {
            $historyId = $this->entityManager->generateEntityId(EntityManager::SERVICE_HISTORY);

            $status = $error ? SystemServiceHistoryStatus::ERROR : SystemServiceHistoryStatus::SUCCESS;

            if(!$this->systemServicesRepository->createHistoryEntry($historyId, $serviceId, $status, implode(' ', $args))) {
                throw new ServiceException('Could not create service history entry.');
            }
        } catch(AException $e) {
            throw new ServiceException('Could not create service history entry.', $e);
        }
    }

    /**
     * Returns service ID
     * 
     * @param string $serviceTitle Service name
     * @return string Service ID
     */
    private function getServiceId(string $serviceTitle) {
        $service = $this->systemServicesRepository->getServiceByTitle($serviceTitle);

        if($service === null) {
            throw new ServiceException('Could not retrieve service information from the database.');
        }

        return $service->getId();
    }

    /**
     * Returns service user ID
     * 
     * @return string|null Service user ID or null
     */
    public function getServiceUserId() {
        $user = $this->userRepository->getUserByUsername('service_user');

        if($user !== null) {
            return $user->getId();
        } else {
            return null;
        }
    }
}

?>