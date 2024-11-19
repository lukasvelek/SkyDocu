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
    private SystemServicesRepository $ssr;
    private UserRepository $ur;
    private EntityManager $em;

    /**
     * Class constructor
     * 
     * @param SystemServicesRepository $ssr SystemServicesRepository instance
     * @param UserRepository $ur UserRepository instance
     */
    public function __construct(SystemServicesRepository $ssr, UserRepository $ur, EntityManager $em) {
        $this->ssr = $ssr;
        $this->ur = $ur;
        $this->em = $em;
    }

    /**
     * Starts a background PHP CLI and runs the given script
     * 
     * @param string $scriptPath Script path to be run in background
     * @return bool True if the script was run successfully or false if not
     */
    public function runService(string $scriptPath) {
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
            $p = popen("start /B " . $cmd, "w");
            if($p === false) {
                return false;
            }
            $status = pclose($p);
            if($status == -1) {
                return false;
            }
        } else {
            $status = exec($cmd . " > /dev/null &");
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

        if(!$this->ssr->updateService($serviceId, ['dateStarted' => date('Y-m-d H:i:s'), 'dateEnded' => NULL, 'status' => SystemServiceStatus::RUNNING])) {
            throw new ServiceException('Could not update service status.');
        }
    }

    /**
     * Updates service status to "Not running" and creates a service history entry
     * 
     * @param string $serviceTitle Service name
     * @param bool $error Finished with error?
     * @throws ServiceException
     */
    public function stopService(string $serviceTitle, bool $error) {
        $serviceId = $this->getServiceId($serviceTitle);

        if(!$this->ssr->updateService($serviceId, ['dateEnded' => date('Y-m-d H:i:s'), 'status' => SystemServiceStatus::NOT_RUNNING])) {
            throw new ServiceException('Could not update service status.');
        }

        try {
            $historyId = $this->em->generateEntityId(EntityManager::SERVICE_HISTORY);

            $status = $error ? SystemServiceHistoryStatus::ERROR : SystemServiceHistoryStatus::SUCCESS;

            if(!$this->ssr->createHistoryEntry($historyId, $serviceId, $status)) {
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
        $service = $this->ssr->getServiceByTitle($serviceTitle);

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
        $user = $this->ur->getUserByUsername('service_user');

        if($user !== null) {
            return $user->getId();
        } else {
            return null;
        }
    }
}

?>