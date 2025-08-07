<?php

namespace App\Repositories\Container;

use App\Constants\Container\ReportRightEntityType;
use App\Repositories\ARepository;
use QueryBuilder\QueryBuilder;

/**
 * ProcessReportRightsRepository contains low-level API methods for process report rights
 * 
 * @author Lukas Velek
 */
class ProcessReportRightsRepository extends ARepository {
    /**
     * Composes a QueryBuilder instance for report rights
     */
    public function composeQueryForReportRights(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('report_rights');

        return $qb;
    }

    /**
     * Returns an array of rights (or a single right) for given entity
     * 
     * @param string $entityId Entity ID
     * @param int $entityType Entity type
     * @param ?string $operation Operation or null
     */
    public function getRightsForEntity(string $entityId, int $entityType, ?string $operation): array {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('report_rights')
            ->where('entityId = ?', [$entityId])
            ->andWhere('entityType = ?', [$entityType]);
            
        if($operation !== null) {
            $qb->andWhere('operation = ?', [$operation]);
        }

        $qb->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[] = $row['operation'];
        }

        return $rights;
    }

    /**
     * Returns an array of rights for a report for given entity
     * 
     * @param string $reportId Report ID
     * @param string $entityId Entity ID
     * @param int $entityType Entity type
     */
    public function getReportRightsForEntity(string $reportId, string $entityId, int $entityType): array {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('report_rights')
            ->where('reportId = ?', [$reportId])
            ->andWhere('entityId = ?', [$entityId])
            ->andWhere('entityType = ?', [$entityType])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[] = $row['operation'];
        }

        return $rights;
    }

    /**
     * Inserts a new report right
     * 
     * @param array $data Data array
     */
    public function insertNewReportRight(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('report_rights', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Removes all rights for report
     * 
     * @param string $reportId Report ID
     */
    public function removeAllReportRights(string $reportId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('report_rights')
            ->where('reportId = ?', [$reportId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Removes a report right by ID
     * 
     * @param string $rightId Right ID
     */
    public function removeReportRightById(string $rightId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('report_rights')
            ->where('rightId = ?', [$rightId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Returns right ID for entity and operation
     * 
     * @param string $reportId Report ID
     * @param string $entityId Entity ID
     * @param int $entityType Entity type
     * @param string $operation Operation name
     */
    public function getRightIdForEntityAndOperation(string $reportId, string $entityId, int $entityType, string $operation): ?string {
        $qb = $this->qb(__METHOD__);

        $qb->select(['rightId'])
            ->from('report_rights')
            ->where('reportId = ?', [$reportId])
            ->andWhere('entityId = ?', [$entityId])
            ->andWhere('entityType = ?', [$entityType])
            ->andWhere('operation = ?', [$operation])
            ->execute();

        return $qb->fetch('rightId');
    }
}