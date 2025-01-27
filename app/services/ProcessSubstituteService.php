<?php

namespace App\Services;

use App\Core\DB\DatabaseManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Managers\ContainerManager;
use App\Managers\EntityManager;
use App\Managers\UserAbsenceManager;
use App\Managers\UserManager;
use App\Managers\UserSubstituteManager;
use App\Repositories\Container\ProcessRepository;
use App\Repositories\ContentRepository;
use App\Repositories\UserAbsenceRepository;
use App\Repositories\UserSubstituteRepository;
use Exception;

class ProcessSubstituteService extends AService {
    private UserAbsenceManager $userAbsenceManager;
    private UserSubstituteManager $userSubstituteManager;
    private ContainerManager $containerManager;
    private DatabaseManager $dbManager;
    private EntityManager $entityManager;
    private UserManager $userManager;

    public function __construct(
        Logger $logger,
        ServiceManager $serviceManager,
        UserAbsenceManager $userAbsenceManager,
        UserSubstituteManager $userSubstituteManager,
        ContainerManager $containerManager,
        DatabaseManager $dbManager,
        EntityManager $entityManager,
        UserManager $userManager
    ) {
        parent::__construct('ProcessSubstitute', $logger, $serviceManager);

        $this->userAbsenceManager = $userAbsenceManager;
        $this->userSubstituteManager = $userSubstituteManager;
        $this->containerManager = $containerManager;
        $this->dbManager = $dbManager;
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
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

        $absentUsersWithSubstitute = $this->getAllAbsentUsersWithSubstitute();
        $this->logInfo(sprintf('Found %d absent users with substitute set.', count($absentUsersWithSubstitute)));

        foreach($containers as $containerId) {
            $this->logInfo(sprintf('Starting processing container \'%s\'.', $containerId));
            $container = $this->containerManager->getContainerById($containerId);
            $containerConnection = $this->dbManager->getConnectionToDatabase($container->databaseName);

            $processRepository = new ProcessRepository($containerConnection, $this->logger);

            $processes = $this->getProcessesWithAbsentUsers($processRepository, $absentUsersWithSubstitute);
            $this->logInfo(sprintf('Found %d processes where the current officer is absent.', count($processes)));

            foreach($processes as $process) {
                $processId = $process['processId'];
                $currentOfficerUserId = $process['currentOfficerUserId'];
                $this->logInfo(sprintf('Processing process ID \'%s\'.', $processId));
                
                $substitute = $absentUsersWithSubstitute[$currentOfficerUserId];
                $this->logInfo(sprintf('Substitute for user \'%s\' is \'%s\'.', $currentOfficerUserId, $substitute));

                $result = $this->updateProcess($processRepository, $processId, $substitute);

                if($result === true) {
                    $this->logInfo(sprintf('Finished processing of process ID \'%s\'.', $processId));
                } else {
                    $this->logError(sprintf('Could not process process ID \'%s\'. Reason: Database error.', $processId));
                }
            }

            $processes = $this->getProcessesWhereCurrentOfficerIsNotAbsentButSubstituteIsSet($processRepository, $absentUsersWithSubstitute);
            $this->logInfo(sprintf('Found %d processes where the current officer is not absent but a substitute for the current officer is set.', count($processes)));

            foreach($processes as $processId) {
                $this->logInfo(sprintf('Processing process ID \'%s\'.', $processId));

                $result = $this->updateProcess($processRepository, $processId, null);

                if($result === true) {
                    $this->logInfo(sprintf('Finished processing of process ID \'%s\'.', $processId));
                } else {
                    $this->logError(sprintf('Could not process process ID \'%s\'. Reason: Database error.', $processId));
                }
            }

            $this->logInfo(sprintf('Processing of container \'%s\' finished.', $containerId));
        }
    }

    private function getAllContainers(): array {
        $qb = $this->containerManager->containerRepository->composeQueryForContainers();
        $qb->execute();

        $containerIds = [];
        while($row = $qb->fetchAssoc()) {
            $containerIds[] = $row['containerId'];
        }

        return $containerIds;
    }

    private function getAllAbsentUsersWithSubstitute(): array {
        $qb = $this->userAbsenceManager->userAbsenceRepository->composeQueryForCurrentlyAbsentUsers();

        $qb->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $userId = $row['userId'];

            $substitute = $this->userSubstituteManager->getUserSubstitute($userId);

            if($substitute !== null) {
                $users[$userId] = $substitute->substituteUserId;
            }
        }

        return $users;
    }

    private function getProcessesWithAbsentUsers(ProcessRepository $processRepository, array $absentUsersWithSubstitute): array {
        $qb = $processRepository->commonComposeQuery();

        $qb->andWhere($qb->getColumnInValues('currentOfficerUserId', array_keys($absentUsersWithSubstitute)))
            ->andWhere('currentOfficerSubstituteUserId IS NULL')
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = [
                'processId' => $row['processId'],
                'currentOfficerUserId' => $row['currentOfficerUserId']
            ];
        }

        return $processes;
    }

    private function getProcessesWhereCurrentOfficerIsNotAbsentButSubstituteIsSet(ProcessRepository $processRepository, array $absentUsersWithSubstitute): array {
        $qb = $processRepository->commonComposeQuery();

        $qb->andWhere($qb->getColumnNotInValues('currentOfficerUserId', array_keys($absentUsersWithSubstitute)))
            ->andWhere('currentOfficerSubstituteUserId IS NOT NULL')
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $row['processId'];
        }

        return $processes;
    }

    private function updateProcess(ProcessRepository $processRepository, string $processId, ?string $substituteUserId): bool {
        return $processRepository->updateProcess($processId, ['currentOfficerSubstituteUserId' => $substituteUserId]);
    }
}

?>