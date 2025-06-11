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
     */
    public function insertNewJob(
        int $type,
        array $params,
        ?DateTime $executionDate
    ) {
        $data = [
            'type' => $type,
            'params' => json_encode($params)
        ];

        if($executionDate === null) {
            $executionDate = new DateTime();
        }

        $data['executionDate'] = $executionDate->getResult();

        $jobId = $this->createId(EntityManager::JOB_QUEUE);
        
        $data['jobId'] = $jobId;

        if(!$this->jobQueueRepository->insertNewJob($data)) {
            throw new GeneralException('Database error.');
        }
    }
}

?>