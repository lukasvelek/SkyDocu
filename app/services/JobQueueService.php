<?php

namespace App\Services;

use App\Constants\Container\ProcessStatus;
use App\Constants\JobQueueProcessingHistoryTypes;
use App\Constants\JobQueueTypes;
use App\Core\Application;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Managers\EntityManager;
use Exception;

class JobQueueService extends AService {
    public function __construct(
        Application $app
    ) {
        parent::__construct('JobQueue', $app);
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
        $queue = $this->app->jobQueueManager->getScheduledJobs();

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

                    case JobQueueTypes::PUBLISH_PROCESS_VERSION_TO_DISTRIBUTION:
                        $this->_PUBLISH_PROCESS_VERSION_TO_DISTRIBUTION($job);
                        break;

                    case JobQueueTypes::CHANGE_PROCESS_VISIBILITY_IN_DISTRIBUTION:
                        $this->_CHANGE_PROCESS_VISIBILITY_IN_DISTRIBUTION($job);
                        break;
                }

                $this->endJob($job);
            } catch(AException $e) {
                $this->errorJob($job, $e);
            }
        }
    }

    /**
     * Parses parameters
     * 
     * @param DatabaseRow $job Job
     */
    private function parseParams(DatabaseRow $job): array {
        $params = $job->params;

        return json_decode($params, true);
    }

    /**
     * Logs job start
     * 
     * @param DatabaseRow $job Job
     */
    private function startJob(DatabaseRow $job) {
        $this->app->jobQueueManager->startJob($job->jobId);
    }

    /**
     * Logs job end
     * 
     * @param DatabaseRow $job Job
     */
    private function endJob(DatabaseRow $job) {
        $this->app->jobQueueManager->endJob($job->jobId);
    }

    /**
     * Logs job error
     * 
     * @param DatabaseRow $job Job
     */
    private function errorJob(DatabaseRow $job, AException $e) {
        $this->app->jobQueueManager->errorJob($job->jobId, $e);
    }

    /**
     * Logs job general message
     * 
     * @param DatabaseRow $job Job
     */
    private function logJob(DatabaseRow $job, string $message) {
        $this->app->jobQueueManager->insertNewProcessingHistoryEntry($job->jobId, JobQueueProcessingHistoryTypes::GENERAL_MESSAGE, $message);
    }

    /**
     * Logs job debug message
     * 
     * @param DatabaseRow $job Job
     */
    private function logDebug(DatabaseRow $job, string $message) {
        $this->app->jobQueueManager->insertNewProcessingHistoryEntry($job->jobId, JobQueueProcessingHistoryTypes::DEBUG_MESSAGE, $message);
    }

    /**
     *                  ======================================
     *                  =            JOB HANDLERS            =
     *                  ======================================
     */

    /**
     * Handles container deleting
     * 
     * @param DatabaseRow $job Job
     */
    private function _DELETE_CONTAINER(DatabaseRow $job) {
        $params = $this->parseParams($job);

        $this->logJob($job, sprintf('Deleting container \'%s\'.', $params['containerId']));
        $this->app->containerManager->deleteContainer($params['containerId']);
        $this->logJob($job, sprintf('Deleted container \'%s\'.', $params['containerId']));
    }

    /**
     * Handles container process instance deleting
     * 
     * @param DatabaseRow $job Job
     */
    private function _DELETE_CONTAINER_PROCESS_INSTANCE(DatabaseRow $job) {
        $params = $this->parseParams($job);
        
        $container = $this->getContainerInstance($params['containerId']);

        $this->logJob($job, sprintf('Deleting process instance \'%s\'.', $params['instanceId']));
        $container->processInstanceManager->deleteProcessInstance($params['instanceId']);
        $this->logJob($job, sprintf('Deleted process instance \'%s\'.', $params['instanceId']));
    }

    /**
     * Handles process version publishing to distribution
     * 
     * @param DatabaseRow $job Job
     */
    private function _PUBLISH_PROCESS_VERSION_TO_DISTRIBUTION(DatabaseRow $job) {
        $params = $this->parseParams($job);

        $processId = $params['processId'];
        $hasMetadata = $params['hasMetadata'] == 1;

        $containers = $this->app->containerManager->getContainersInDistribution();

        $process = $this->app->processManager->getProcessEntityById($processId);

        $this->logJob($job, sprintf('Found process ID \'%s\' that will be published to containers (count: %d).', $processId, count($containers)));

        foreach($containers as $_container) {
            $containerId = $_container->getId();
            
            $this->logJob($job, sprintf('Processing publishing to container \'%s\'.', $containerId));

            try {
                $container = $this->getContainerInstance($containerId);

                $disable = false;

                $container->processRepository->beginTransaction(__METHOD__);

                try {
                    $this->logJob($job, sprintf('Checking if container \'%s\' has a previous version.', $containerId));
                    $lastProcess = $container->processManager->getLastProcessForUniqueProcessId($process->getUniqueProcessId());

                    if($lastProcess->isEnabled == false) {
                        $disable = true;
                    }

                    $this->logJob($job, sprintf('Previous version found.'));
                } catch(AException $e) {
                    $this->logJob($job, sprintf('Previous version not found.'));
                }

                $this->logJob($job, sprintf('Removing current process version form distribution.'));
                $container->processRepository->removeCurrentDistributionProcessFromDistributionForUniqueProcessId($process->getUniqueProcessId());

                $this->logJob($job, sprintf('Adding new process version to the container.'));
                $container->processRepository->addNewProcess(
                    $processId,
                    $process->getUniqueProcessId(),
                    $process->getTitle(),
                    $process->getDescription(),
                    base64_encode(json_encode($process->getDefinition())),
                    $this->app->userManager->getServiceUserId(),
                    ProcessStatus::IN_DISTRIBUTION,
                    !$disable
                );

                if($hasMetadata) {
                    $this->logJob($job, 'Process version has metadata.');
                    $qb = $container->processMetadataRepository->composeQueryForProcessMetadata($process->getUniqueProcessId());
                    $qb->execute();

                    $cMetadata = [];
                    $delete = [];
                    while($row = $qb->fetchAssoc()) {
                        foreach($process->getMetadataDefinition()['metadata'] as $m) {
                            if($m['type'] != $row['type'] &&
                                $m['name'] == $row['title'] &&
                                $m['label'] != $row['guiTitle']) {
                                $delete[] = $row['metadataId'];
                                $cMetadata[] = $m['name'];
                            }
                        }
                    }

                    $this->logJob($job, sprintf('Removing all previous metadata for process.'));
                    foreach($delete as $metadataId) {
                        $container->processMetadataRepository->removeMetadataValuesForMetadataId($metadataId);
                        $container->processMetadataRepository->removeMetadata($metadataId);
                    }

                    $this->logJob($job, sprintf('Creating new metadata for process.'));
                    foreach($cMetadata as $name) {
                        $data = [
                            'metadataId' => $container->entityManager->generateEntityId(EntityManager::C_PROCESS_CUSTOM_METADATA),
                            'uniqueProcessId' => $process->getUniqueProcessId(),
                            'title' => $name,
                            'guiTitle' => $process->getMetadataDefinitionForMetadataName($name)['label'],
                            'type' => $process->getMetadataDefinitionForMetadataName($name)['type'],
                            'defaultValue' => $process->getMetadataDefinitionForMetadataName($name)['defaultValue'],
                            'isRequired' => 1,
                            'isSystem' => 1
                        ];

                        $container->processMetadataRepository->insertNewMetadata($data);
                    }
                }

                $container->processRepository->commit($this->app->userManager->getServiceUserId(), __METHOD__);

                $this->logJob($job, sprintf('Finished processing publishing to container \'%s\'.', $containerId));
            } catch(AException $e) {
                $container->processRepository->rollback(__METHOD__);

                $this->errorJob($job, $e);
            }
        }
    }

    private function _CHANGE_PROCESS_VISIBILITY_IN_DISTRIBUTION(DatabaseRow $job) {
        $params = $this->parseParams($job);

        $processId = $params['processId'];

        $containers = $this->app->containerManager->getContainersInDistribution();
        $process = $this->app->processManager->getProcessEntityById($processId);

        $this->logJob($job, sprintf('Changing visibility of process \'%s\'.', $processId));
        $this->logJob($job, sprintf('Found %d containers in distribution.', count($containers)));

        foreach($containers as $_container) {
            $containerId = $_container->getId();
            
            try {
                $this->logJob($job, sprintf('Processing container \'%s\'.', $containerId));

                $container = $this->getContainerInstance($containerId);

                $container->processRepository->beginTransaction(__METHOD__);

                $container->processManager->updateProcess($processId, [
                    'isVisible' => ($process->isVisible() ? 1 : 0)
                ]);

                $container->processRepository->commit($this->app->userManager->getServiceUserId(), __METHOD__);

                $this->logJob($job, sprintf('Container \'%s\' processed.', $containerId));
            } catch(AException $e) {
                $container->processRepository->rollback(__METHOD__);

                $this->errorJob($job, $e);
            }
        }
    }

    /**
     *                  ======================================
     *                  =       END OF JOB HANDLERS          =
     *                  ======================================
     */
}

?>