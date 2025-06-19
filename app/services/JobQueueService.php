<?php

namespace App\Services;

use App\Constants\JobQueueProcessingHistoryTypes;
use App\Constants\JobQueueTypes;
use App\Core\Application;
use App\Core\Container;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
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
     *                  ======================================
     *                  =       END OF JOB HANDLERS          =
     *                  ======================================
     */
}

?>