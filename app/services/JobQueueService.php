<?php

namespace App\Services;

use App\Constants\JobQueueProcessingHistoryTypes;
use App\Constants\JobQueueTypes;
use App\Core\DatabaseConnection;
use App\Core\DB\DatabaseManager;
use App\Core\DB\DatabaseRow;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessInstanceManager;
use App\Managers\ContainerManager;
use App\Managers\EntityManager;
use App\Managers\JobQueueManager;
use App\Managers\UserManager;
use App\Repositories\Container\GroupRepository;
use App\Repositories\Container\ProcessInstanceRepository;
use App\Repositories\ContentRepository;
use App\Repositories\TransactionLogRepository;
use Exception;

class JobQueueService extends AService {
    private JobQueueManager $jobQueueManager;
    private ContainerManager $containerManager;
    private DatabaseManager $dbManager;
    private UserManager $userManager;

    private ?TransactionLogRepository $_transactionLogRepository = null;

    public function __construct(
        Logger $logger,
        ServiceManager $serviceManager,
        JobQueueManager $jobQueueManager,
        ContainerManager $containerManager,
        DatabaseManager $dbManager,
        UserManager $userManager
    ) {
        parent::__construct('JobQueue', $logger, $serviceManager);

        $this->jobQueueManager = $jobQueueManager;
        $this->containerManager = $containerManager;
        $this->dbManager = $dbManager;
        $this->userManager = $userManager;
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop($e);
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $queue = $this->jobQueueManager->getScheduledJobs();

        if(empty($queue)) {
            return;
        }

        foreach($queue as $job) {
            try {
                $this->startJob($job);

                switch($job->type) {
                    case JobQueueTypes::DELETE_CONTAINER:
                        $this->_DELETE_CONTAINER($job);
                        break;

                    case JobQueueTypes::DELETE_CONTAINER_PROCESS_INSTANCE:
                        $this->_DELETE_CONTAINER_PROCESS_INSTANCE($job);
                        break;
                }

                $this->endJob($job);
            } catch(AException $e) {
                $this->errorJob($job, $e);
            }
        }
    }

    private function _DELETE_CONTAINER(DatabaseRow $job) {
        $params = $this->parseParams($job);

        $this->logJob($job, sprintf('Deleting container \'%s\'.', $params['containerId']));
        $this->containerManager->deleteContainer($params['containerId']);
        $this->logJob($job, sprintf('Deleted container \'%s\'.', $params['containerId']));
    }

    private function _DELETE_CONTAINER_PROCESS_INSTANCE(DatabaseRow $job) {
        $params = $this->parseParams($job);

        $conn = $this->getContainerConnection($params['containerId']);

        /**
         * @var ContentRepository $contentRepository
         */
        $contentRepository = $this->getContainerRepositoryObject($conn, ContentRepository::class);
        /**
         * @var ProcessInstanceRepository $processInstanceRepository
         */
        $processInstanceRepository = $this->getContainerRepositoryObject($conn, ProcessInstanceRepository::class);
        /**
         * @var GroupRepository $groupRepository
         */
        $groupRepository = $this->getContainerRepositoryObject($conn, GroupRepository::class);
        
        try {
            $entityManager = new EntityManager($this->logger, $contentRepository);

            $groupManager = new GroupManager($this->logger, $entityManager, $groupRepository, $this->userManager->userRepository);
            
            $processInstanceManager = new ProcessInstanceManager($this->logger, $entityManager, $processInstanceRepository, $groupManager, $this->userManager);
        } catch(AException|Exception $e) {
            throw new GeneralException('Could not create instance of ProcessInstanceManager. Reason: ' . $e->getMessage());
        }

        $this->logJob($job, sprintf('Deleting process instance \'%s\'.', $params['instanceId']));
        $processInstanceManager->deleteProcessInstance($params['instanceId']);
        $this->logJob($job, sprintf('Deleted process instance \'%s\'.', $params['instanceId']));
    }

    private function parseParams(DatabaseRow $job): array {
        $params = $job->params;

        return json_decode($params, true);
    }

    private function startJob(DatabaseRow $job) {
        $this->jobQueueManager->startJob($job->jobId);
    }

    private function endJob(DatabaseRow $job) {
        $this->jobQueueManager->endJob($job->jobId);
    }

    private function errorJob(DatabaseRow $job, AException $e) {
        $this->jobQueueManager->errorJob($job->jobId, $e);
    }

    private function logJob(DatabaseRow $job, string $message) {
        $this->jobQueueManager->insertNewProcessingHistoryEntry($job->jobId, JobQueueProcessingHistoryTypes::GENERAL_MESSAGE, $message);
    }

    private function getContainerConnection(string $containerId): DatabaseConnection {
        $container = $this->containerManager->getContainerById($containerId, true);
        
        return $this->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());
    }

    private function getContainerRepositoryObject(DatabaseConnection $conn, string $name) {
        if(!class_exists($name)) {
            throw new GeneralException(sprintf('Class \'%s\' is undefined.', $name));
        }

        if($this->_transactionLogRepository === null) {
            $this->_transactionLogRepository = new TransactionLogRepository($conn, $this->logger);
        }

        try {
            $obj = new $name($conn, $this->logger, $this->_transactionLogRepository, $this->serviceManager->getServiceUserId());
        } catch(AException|Exception $e) {
            throw new GeneralException(sprintf('Could not create instance of \'%s\'.', $name));
        }

        return $obj;
    }
}

?>