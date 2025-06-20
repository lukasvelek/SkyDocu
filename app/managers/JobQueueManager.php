<?php

namespace App\Managers;

use App\Constants\JobQueueProcessingHistoryTypes;
use App\Constants\JobQueueStatus;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\JobQueueProcessingHistoryRepository;
use App\Repositories\JobQueueRepository;

/**
 * JobQueueManager contains high-level API methods for job queue
 * 
 * @author Lukas Velek
 */
class JobQueueManager extends AManager {
    private JobQueueRepository $jobQueueRepository;
    private JobQueueProcessingHistoryRepository $jobQueueProcessingHistoryRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, JobQueueRepository $jobQueueRepository, JobQueueProcessingHistoryRepository $jobQueueProcessingHistoryRepository) {
        parent::__construct($logger, $entityManager);

        $this->jobQueueRepository = $jobQueueRepository;
        $this->jobQueueProcessingHistoryRepository = $jobQueueProcessingHistoryRepository;
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
            'statusText' => $statusMessage
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

    /**
     * Inserts a new processing history entry
     * 
     * @param ?string $jobId Job ID or null
     * @param int $type Type
     * @param ?string $description Description or null
     * @throws GeneralException
     */
    public function insertNewProcessingHistoryEntry(?string $jobId, int $type, ?string $description) {
        $entryId = $this->createId(EntityManager::JOB_QUEUE_PROCESSING_HISTORY);

        $data = [
            'entryId' => $entryId,
            'type' => $type
        ];

        if($jobId !== null) {
            $data['jobId'] = $jobId;
        }
        if($description !== null) {
            $data['description'] = $description;
        }

        if(!$this->jobQueueProcessingHistoryRepository->insertNewEntry($data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Returns an array of scheduled jobs
     */
    public function getScheduledJobs(): array {
        $qb = $this->jobQueueRepository->composeQueryForScheduledJobs()
            ->execute();

        $jobs = [];
        while($row = $qb->fetchAssoc()) {
            $jobs[] = DatabaseRow::createFromDbRow($row);
        }

        return $jobs;
    }

    /**
     * Starts a job
     * 
     * @param string $jobId Job ID
     */
    public function startJob(string $jobId) {
        // change status
        $this->changeJobStatusWithMessage($jobId, JobQueueStatus::IN_PROGRESS, 'Job started');

        // create processing history entry
        $this->insertNewProcessingHistoryEntry($jobId, JobQueueProcessingHistoryTypes::JOB_PROCESSING_STARTED, 'Job started');
    }

    /**
     * Ends a job
     * 
     * @param string $jobId Job ID
     */
    public function endJob(string $jobId) {
        // change status
        $this->changeJobStatusWithMessage($jobId, JobQueueStatus::FINISHED, 'Job ended');

        // create processing history entry
        $this->insertNewProcessingHistoryEntry($jobId, JobQueueProcessingHistoryTypes::JOB_PROCESSING_ENDED, 'Job ended');
    }

    /**
     * Ends a job due to exception
     * 
     * @param string $jobId Job ID
     * @param AException $e Exception thrown that caused the unexpected end
     */
    public function errorJob(string $jobId, AException $e) {
        // change status
        $this->changeJobStatusWithMessage($jobId, JobQueueStatus::ERROR, 'Job unexpectedly ended due to exception');

        // create processing history entry
        $this->insertNewProcessingHistoryEntry($jobId, JobQueueProcessingHistoryTypes::ERROR_MESSAGE, 'Job unexpectedly ended due to exception');
        $this->insertNewProcessingHistoryEntry($jobId, JobQueueProcessingHistoryTypes::ERROR_MESSAGE, 'Exception: ' . $e->getMessage() . ' [#' . $e->getHash() . ']');
    }
}

?>