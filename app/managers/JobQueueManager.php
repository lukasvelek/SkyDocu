<?php

namespace App\Managers;

use App\Core\Datetypes\DateTime;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\JobQueueRepository;

/**
 * JobQueueManager contains high-level API methods for job queue
 * 
 * @author Lukas Velek
 */
class JobQueueManager extends AManager {
    private JobQueueRepository $jobQueueRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, JobQueueRepository $jobQueueRepository) {
        parent::__construct($logger, $entityManager);

        $this->jobQueueRepository = $jobQueueRepository;
    }

    /**
     * Inserts a new job to the job queue
     * 
     * @param int $type Job type
     * @param array $params Params
     * @param ?DateTime $executionDate Scheduled execution date or null for instant execution
     * @throws GeneralException
     */
    public function insertNewJob(
        int $type,
        array $params,
        ?DateTime $executionDate
    ) {
        $jobId = $this->createId(EntityManager::JOB_QUEUE);

        $data = [
            'jobId' => $jobId,
            'type' => $type,
            'params' => json_encode($params),
            'dateModified' => DateTime::now()
        ];

        if($executionDate === null) {
            $executionDate = new DateTime();
        }

        $data['executionDate'] = $executionDate->getResult();

        if(!$this->jobQueueRepository->insertNewJob($data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Updates a job in the queue
     * 
     * @param string $jobId Job ID
     * @param array $data Data array
     * @throws GeneralException
     */
    public function updateJob(
        string $jobId,
        array $data
    ) {
        if(!array_key_exists('dateModified', $data)) {
            $data['dateModified'] = DateTime::now();
        }

        if(!$this->jobQueueRepository->updateJob($jobId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Changes job status with status message
     * 
     * @param string $jobId Job ID
     * @param int $newStatus New status
     * @param ?string $statusMessage Status message
     * @throws GeneralException
     * 
     * If status message is null then it will be erased in the database. For keeping the status message use JobQueueManager::changeJobStatus().
     */
    public function changeJobStatusWithMessage(
        string $jobId,
        int $newStatus,
        ?string $statusMessage
    ) {
        $data = [
            'status' => $newStatus,
            'statusMessage' => $statusMessage
        ];

        $this->updateJob($jobId, $data);
    }

    /**
     * Changes job status without status message
     * 
     * @param string $jobId Job ID
     * @param int $newStatus New status
     * @throws GeneralException
     * 
     * No status message is updated. For updating the status message use JobQueueManager::changeJobStatusWithMessage().
     */
    public function changeJobStatus(
        string $jobId,
        int $newStatus
    ) {
        $data = [
            'status' => $newStatus
        ];

        $this->updateJob($jobId, $data);
    }
}

?>