<?php

namespace App\Repositories;

use QueryBuilder\QueryBuilder;

/**
 * JobQueueProcessingHistoryRepository contains low-level methods for working with history entries of job queue processing
 * 
 * @author Lukas Velek
 */
class JobQueueProcessingHistoryRepository extends ARepository {
    /**
     * Composes common query for processing history
     */
    public function commonComposeQuery(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('job_queue_processing_history');

        return $qb;
    }

    /**
     * Inserts a new entry
     * 
     * @param array $data Data array
     */
    public function insertNewEntry(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('job_queue_processing_history', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Composes query for processing history of job ID
     * 
     * @param string $jobId Job ID
     */
    public function composeQueryForJobId(string $jobId): QueryBuilder {
        $qb = $this->commonComposeQuery();
        $qb->andWhere('jobId = ?', [$jobId]);

        return $qb;
    }
}

?>