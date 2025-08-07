<?php

namespace App\Managers\Container;

use App\Constants\Container\ReportRightEntityType;
use App\Constants\Container\ReportRightOperations;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ProcessReportRightsRepository;
use App\Repositories\Container\ProcessReportsRepository;
use QueryBuilder\QueryBuilder;

/**
 * ProcessReportManager contains high-level API methods for process reports
 * 
 * @author Lukas Velek
 */
class ProcessReportManager extends AManager {
    private ProcessReportsRepository $processReportsRepository;
    private ProcessReportRightsRepository $processReportRightsRepository;
    private GroupManager $groupManager;

    public function __construct(
        Logger $logger,
        EntityManager $entityManager,
        ProcessReportsRepository $processReportsRepository,
        ProcessReportRightsRepository $processReportRightsRepository,
        GroupManager $groupManager
    ) {
        parent::__construct($logger, $entityManager);

        $this->processReportsRepository = $processReportsRepository;
        $this->processReportRightsRepository = $processReportRightsRepository;
        $this->groupManager = $groupManager;
    }

    /**
     * Composes a QueryBuilder instance for all visible reports for user ID
     * 
     * @param string $userId User ID
     */
    public function composeQueryForAllVisibleReports(string $userId): QueryBuilder {
        $groupIds = $this->groupManager->getGroupsForUser($userId);

        $qbRights = $this->processReportsRepository->getQb(__METHOD__);

        $qbRights->select(['reportId'])
            ->from('report_rights')
            ->where('((' . $qbRights->getColumnInValues('entityId', $groupIds) . ' AND entityType = ' . ReportRightEntityType::GROUP . ') OR (entityId = ? AND entityType = ' . ReportRightEntityType::USER . '))', [$userId])
            ->andWhere('operation = ?', [ReportRightOperations::READ]);

        $qb = $this->processReportsRepository->getQb(__METHOD__);

        $qb->select(['*'])
            ->from('reports')
            ->where('reportId IN (' . $qbRights->getSQL() . ')');

        return $qb;
    }
}