<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;
use QueryBuilder\QueryBuilder;

/**
 * ProcessReportsRepository contains low-level API methods for process reports
 * 
 * @author Lukas Velek
 */
class ProcessReportsRepository extends ARepository {
    /**
     * Composes a QueryBuilder instance for process reports
     */
    public function composeQueryForProcessReports(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('reports');

        return $qb;
    }

    /**
     * Inserts a new report
     * 
     * @param array $data Data array
     */
    public function insertNewReport(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('reports', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Updates a report
     * 
     * @param string $reportId Report ID
     * @param array $data Data array
     */
    public function updateReport(string $reportId, array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->update('reports')
            ->set($data)
            ->where('reportId = ?', [$reportId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Deletes a report
     * 
     * @param string $reportId Report ID
     */
    public function deleteReport(string $reportId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('reports')
            ->where('reportId = ?', [$reportId])
            ->execute();

        return $qb->fetchBool();
    }
}