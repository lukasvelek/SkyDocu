<?php

namespace App\Components\ProcessesGrid;

use App\Constants\Container\ProcessGridViews;
use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Constants\Container\ProcessInstanceStatus;
use App\Managers\Container\GroupManager;
use App\Repositories\Container\ProcessInstanceRepository;
use QueryBuilder\QueryBuilder;

/**
 * ProcessGridDatasourceHelper helps ProcessesGrid with datasource
 * 
 * @author Lukas Velek
 */
class ProcessesGridDatasourceHelper {
    private string $view;
    private ProcessInstanceRepository $processInstanceRepository;
    private string $currentUserId;
    private GroupManager $groupManager;

    /**
     * Class constructor
     * 
     * @param string $view View name
     * @param ProcessInstanceRepository $processInstanceRepository ProcessInstanceRepository instance
     * @param string $currentUserId Current user ID
     * @param GroupManager $groupManager GroupManager instance
     */
    public function __construct(string $view, ProcessInstanceRepository $processInstanceRepository, string $currentUserId, GroupManager $groupManager) {
        $this->view = $view;
        $this->processInstanceRepository = $processInstanceRepository;
        $this->currentUserId = $currentUserId;
        $this->groupManager = $groupManager;
    }

    /**
     * Returns a QueryBuilder instance for given view or null
     */
    public function composeQb(): ?QueryBuilder {
        switch($this->view) {
            case ProcessGridViews::VIEW_ALL:
                return $this->composeQueryForAll();

            case ProcessGridViews::VIEW_STARTED_BY_ME:
                return $this->composeQueryForStartedByMe();

            case ProcessGridViews::VIEW_WAITING_FOR_ME:
                return $this->composeQueryForWaitingForMe();
            
            default:
                return null;
        }
    }

    /**
     * Composes query for all processes
     */
    private function composeQueryForAll(): QueryBuilder {
        return $this->processInstanceRepository->commonComposeQuery();
    }

    /**
     * Composes query for processes waiting for me
     */
    private function composeQueryForWaitingForMe(): QueryBuilder {
        $qb = $this->processInstanceRepository->commonComposeQuery();

        $qb->andWhere($qb->getColumnInValues('status', [ProcessInstanceStatus::NEW, ProcessInstanceStatus::IN_PROGRESS]));

        $sqlForOfficer = "
            (
                (
                    currentOfficerType = " . ProcessInstanceOfficerTypes::USER . "
                        AND
                    currentOfficerId = \'" . $this->currentUserId . "\'
                )
                    OR
                (
                    currentOfficerType = " . ProcessInstanceOfficerTypes::GROUP . "
                        AND
                    currentOfficerId IN (" . implode(', ', $this->getGroupIdsWhereUserIsMember()) . ")
                )
            )
        ";

        $qb->andWhere($sqlForOfficer);
        
        return $qb;
    }

    /**
     * Composes query for processes started by me
     */
    private function composeQueryForStartedByMe() {
        $qb = $this->processInstanceRepository->commonComposeQuery();

        $qb->andWhere($qb->getColumnInValues('status', [ProcessInstanceStatus::NEW, ProcessInstanceStatus::IN_PROGRESS]))
            ->andWhere('userId = ?', [$this->currentUserId]);
        
        return $qb;
    }

    /**
     * Returns an array of IDs of groups current user is member of
     */
    private function getGroupIdsWhereUserIsMember(): array {
        $groupIds = [];

        $qb = $this->groupManager->composeQueryForGroupsWhereUserIsMember($this->currentUserId);
        $qb->execute();

        while($row = $qb->fetchAssoc()) {
            $groupIds[] = '\'' . $row['groupId'] . '\'';
        }

        return $groupIds;
    }
}

?>