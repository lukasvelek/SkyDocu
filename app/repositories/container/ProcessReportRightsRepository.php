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
     * Returns rights for user
     * 
     * @param string $userId User ID
     * @param ?string $operation Operation or null
     */
    public function getRightsForUser(string $userId, ?string $operation): array {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('report_rights')
            ->where('entityId = ?', [$userId])
            ->andWhere('entityType = ?', [ReportRightEntityType::USER]);
            
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
     * Returns rights for group
     * 
     * @param string $groupId Group ID
     * @param ?string $operation Operation or null
     */
    public function getRightsForGroup(string $groupId, ?string $operation): array {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('report_rights')
            ->where('entityId = ?', [$groupId])
            ->andWhere('entityType = ?', [ReportRightEntityType::GROUP]);

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
     * Returns an array of rights for report for user
     * 
     * @param string $reportId Report ID
     * @param string $userId User ID
     */
    public function getReportRightsForUser(string $reportId, string $userId): array {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('report_rights')
            ->where('reportId = ?', [$reportId])
            ->andWhere('entityId = ?', [$userId])
            ->andWhere('entityType = ?', [ReportRightEntityType::USER])
            ->execute();

        $rights = [];
        while($row = $qb->fetchAssoc()) {
            $rights[] = $row['operation'];
        }

        return $rights;
    }

    /**
     * Returns an array of rights for report for group
     * 
     * @param string $reportId Report ID
     * @param string $groupId Group ID
     */
    public function getReportRightsForGroup(string $reportId, string $groupId): array {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('report_rights')
            ->where('reportId = ?', [$reportId])
            ->andWhere('entityId = ?', [$groupId])
            ->andWhere('entityType = ?', [ReportRightEntityType::GROUP])
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
}