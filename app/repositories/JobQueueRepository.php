<?php

namespace App\Repositories;

use App\Constants\JobQueueStatus;
use QueryBuilder\QueryBuilder;

/**
 * JobQueueRepository contains low-level API methods for job queue
 * 
 * @author Lukas Velek
 */
class JobQueueRepository extends ARepository {
    /**
     * Composes common query and returns
     */
    public function commonComposeQuery(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('job_queue');

        return $qb;
    }

    /**
     * Composes query for scheduled jobs
     */
    public function composeQueryForScheduledJobs(): QueryBuilder {
        $qb = $this->commonComposeQuery();

        $date = date('Y-m-d H:i:s');

        $qb->where('status = ?', [JobQueueStatus::NEW])
            ->andWhere('executionDate <= ?', [$date]);

        return $qb;
    }

    /**
     * Inserts a new job
     * 
     * @param array $data Data array
     */
    public function insertNewJob(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('job_queue', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Updates a job
     * 
     * @param string $jobId Job ID
     * @param array $data Data array
     */
    public function updateJob(string $jobId, array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->update('job_queue')
            ->set($data)
            ->where('jobId = ?', [$jobId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>