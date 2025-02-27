<?php

namespace App\Services;

use App\Constants\Container\StandaloneProcesses;
use App\Core\DB\DatabaseManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessManager;
use App\Managers\ContainerManager;
use App\Managers\EntityManager;
use App\Managers\UserAbsenceManager;
use App\Managers\UserSubstituteManager;
use App\Repositories\Container\GroupRepository;
use App\Repositories\Container\ProcessRepository;
use App\Repositories\ContentRepository;
use App\Repositories\UserAbsenceRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserSubstituteRepository;
use Error;
use Exception;

class ContainerStandaloneProcessCheckerService extends AService {
    private ContainerManager $containerManager;
    private UserRepository $userRepository;
    private DatabaseManager $dbManager;
    private EntityManager $entityManager;

    public function __construct(Logger $logger, ServiceManager $serviceManager, ContainerManager $containerManager, UserRepository $userRepository, DatabaseManager $dbManager, EntityManager $entityManager) {
        parent::__construct('ContainerStandaloneProcessChecker', $logger, $serviceManager);

        $this->containerManager = $containerManager;
        $this->userRepository = $userRepository;
        $this->dbManager = $dbManager;
        $this->entityManager = $entityManager;
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

        $this->logInfo('Obtaining containers...');

        $containers = $this->getAllContainers();
        
        $this->logInfo(sprintf('Found %d containers.', count($containers)));

        foreach($containers as $containerId) {
            $this->logInfo(sprintf('Starting processing container \'%s\'.', $containerId));
            $container = $this->containerManager->getContainerById($containerId);
            $containerConnection = $this->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

            $contentRepository = new ContentRepository($containerConnection, $this->logger);
            $entityManager = new EntityManager($this->logger, $contentRepository);
            $userSubstituteRepository = new UserSubstituteRepository($this->serviceManager->systemServicesRepository->conn, $this->logger);
            $userAbsenceRepository = new UserAbsenceRepository($this->serviceManager->systemServicesRepository->conn, $this->logger);
            $processRepository = new ProcessRepository($containerConnection, $this->logger);
            $groupRepository = new GroupRepository($containerConnection, $this->logger);

            $groupManager = new GroupManager($this->logger, $entityManager, $groupRepository, $this->userRepository);
            $userSubstituteManager = new UserSubstituteManager($this->logger, $entityManager, $userSubstituteRepository);
            $userAbsenceManager = new UserAbsenceManager($this->logger, $entityManager, $userAbsenceRepository);
            $processManager = new ProcessManager($this->logger, $entityManager, $processRepository, $groupManager, $userSubstituteManager, $userAbsenceManager);

            $qb = $processManager->processRepository->composeQueryForProcessTypes();
            $qb->execute();

            $processTypes = [];
            while($row = $qb->fetchAssoc()) {
                $processTypes[] = $row['typeKey'];
            }

            $this->logInfo(sprintf('Found %d process types.', count($processTypes)));

            $notFoundInDb = [];
            foreach(StandaloneProcesses::getAll() as $key => $title) {
                if(!in_array($key, $processTypes)) {
                    $notFoundInDb[] = $key;
                }
            }

            $this->logInfo(sprintf('%d processes were not found in the database.', count($notFoundInDb)));

            $toDeleteInDb = [];
            foreach($processTypes as $typeKey) {
                if(!in_array($typeKey, StandaloneProcesses::getAll())) {
                    $toDeleteInDb[] = $typeKey;
                }
            }

            $this->logInfo(sprintf('%d processes were probably deprecated and are not used and thus are ready to be deleted.', count($toDeleteInDb)));

            $this->logInfo('Processing deprecated processes that are ready to be deleted.');
            foreach($toDeleteInDb as $typeKey) {
                $this->logInfo(sprintf('Deleting process type \'%s\'.', $typeKey));
                $processManager->deleteProcessType($typeKey);
            }

            $this->logInfo('Processing not found processes.');
            foreach($notFoundInDb as $typeKey) {
                $this->logInfo(sprintf('Inserting process type \'%s\'.', $typeKey));
                $processManager->insertNewProcessType(
                    $typeKey,
                    StandaloneProcesses::toString($typeKey),
                    StandaloneProcesses::getDescription($typeKey)
                );
            }
        }
    }

    private function getAllContainers() {
        $qb = $this->containerManager->containerRepository->composeQueryForContainers();
        $qb->execute();

        $containerIds = [];
        while($row = $qb->fetchAssoc()) {
            $containerIds[] = $row['containerId'];
        }

        return $containerIds;
    }
}

?>