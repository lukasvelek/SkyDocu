<?php

namespace App\Managers\Container;

use App\Constants\Container\ReportRightEntityType;
use App\Constants\Container\ReportRightOperations;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
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
     * @param bool $enabledOnly Enabled only?
     */
    public function composeQueryForAllVisibleReports(string $userId, bool $enabledOnly = true): QueryBuilder {
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

        if($enabledOnly === true) {
            $qb->andWhere('isEnabled = 1');
        }

        return $qb;
    }
    
    /**
     * Creates a new report and returns its ID
     * 
     * @param string $userId User ID
     * @param string $title Title
     * @param ?string $description Description
     */
    public function createNewReport(
        string $userId,
        string $title,
        ?string $description
    ): string {
        $reportId = $this->createId(EntityManager::C_REPORTS);

        $data = [
            'reportId' => $reportId,
            'title' => $title,
            'userId' => $userId,
            'isEnabled' => false
        ];

        if($description !== null) {
            $data['description'] = $description;
        }

        if(!$this->processReportsRepository->insertNewReport($data)) {
            throw new GeneralException('Database error.');
        }

        $this->grantAllReportRightsToEntity($reportId, $userId, ReportRightEntityType::USER);

        return $reportId;
    }

    /**
     * Updates report
     * 
     * @param string $reportId Report ID
     * @param array $data Data array
     */
    public function updateReport(string $reportId, array $data) {
        if(!$this->processReportsRepository->updateReport($reportId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Deletes all report rights
     * 
     * @param string $reportId Report ID
     * @throws GeneralException
     */
    public function deleteAllReportRights(string $reportId) {
        if(!$this->processReportRightsRepository->removeAllReportRights($reportId)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Grants all report rights to entity
     * 
     * @param string $reportId Report ID
     * @param string $entityId Entity ID
     * @param int $entityType Entity type
     * @throws GeneralException
     */
    public function grantAllReportRightsToEntity(string $reportId, string $entityId, int $entityType) {
        $operations = [
            ReportRightOperations::DELETE,
            ReportRightOperations::EDIT,
            ReportRightOperations::GRANT,
            ReportRightOperations::READ
        ];

        foreach($operations as $operation) {
            $this->grantReportRightToEntity($reportId, $entityId, $entityType, $operation);
        }
    }

    /**
     * Revokes all report rights to entity
     * 
     * @param string $reportId Report ID
     * @param string $entityId Entity ID
     * @param int $entityType Entity type
     * @throws GeneralException
     */
    public function revokeAllReportRightsToEntity(string $reportId, string $entityId, int $entityType) {
        $operations = $this->processReportRightsRepository->getReportRightsForEntity($reportId, $entityId, $entityType);

        foreach($operations as $operation) {
            $this->revokeReportRightToEntity($reportId, $entityId, $entityType, $operation);
        }
    }

    /**
     * Grants report right to entity to operation
     * 
     * @param string $reportId Report ID
     * @param string $entityId Entity ID
     * @param int $entityType Entity type
     * @param string $operation Operation name
     * @throws GeneralException
     */
    public function grantReportRightToEntity(string $reportId, string $entityId, int $entityType, string $operation) {
        $rightId = $this->createId(EntityManager::C_REPORT_RIGHTS);

        $data = [
            'rightId' => $rightId,
            'reportId' => $reportId,
            'entityId' => $entityId,
            'entityType' => $entityType,
            'operation' => $operation
        ];

        if(!$this->processReportRightsRepository->insertNewReportRight($data)) {
            throw new GeneralException('Database error.');
        }

        if($operation != ReportRightOperations::READ) {
            $this->grantReportRightToEntity($reportId, $entityId, $entityType, ReportRightOperations::READ);
        }
    }

    /**
     * Revokes report right to entity to operation
     * 
     * @param string $reportId Report ID
     * @param string $entityId Entity ID
     * @param int $entityType Entity type
     * @param string $operation Operation name
     * @throws GeneralException
     */
    public function revokeReportRightToEntity(string $reportId, string $entityId, int $entityType, string $operation) {
        $rightId = $this->processReportRightsRepository->getRightIdForEntityAndOperation($reportId, $entityId, $entityType, $operation);

        if($rightId === null) {
            return;
        }

        if(!$this->processReportRightsRepository->removeReportRightById($rightId)) {
            throw new GeneralException('Database error.');
        }

        if($operation == ReportRightOperations::READ) {
            // delete all other

            $this->revokeAllReportRightsToEntity($reportId, $entityId, $entityType);
        }
    }

    /**
     * Returns an array of operation rights for report for given user
     * 
     * @param string $reportId Report ID
     * @param string $userId User ID
     */
    public function getReportRightsForUser(string $reportId, string $userId): array {
        return $this->processReportRightsRepository->getReportRightsForEntity($reportId, $userId, ReportRightEntityType::USER);
    }

    /**
     * Returns an array of operation rights for report for given group
     * 
     * @param string $reportId Report ID
     * @param string $groupId Group ID
     */
    public function getReportRightsForGroup(string $reportId, string $groupId): array {
        return $this->processReportRightsRepository->getReportRightsForEntity($reportId, $groupId, ReportRightEntityType::GROUP);
    }

    /**
     * Returns report by ID
     * 
     * @param string $reportId Report ID
     */
    public function getReportById(string $reportId): DatabaseRow {
        $row = $this->processReportsRepository->getReportById($reportId);

        if($row === null) {
            throw new NonExistingEntityException('Report does not exist.');
        }

        return DatabaseRow::createFromDbRow($row);
    }
}