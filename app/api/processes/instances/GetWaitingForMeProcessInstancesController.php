<?php

namespace App\Api\Processes\Instances;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\Container\ExternalSystemRightsOperations;
use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Constants\Container\ProcessInstanceStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetWaitingForMeProcessInstancesController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        if(!$this->checkRight(ExternalSystemRightsOperations::READ_PROCESSES)) {
            return new JsonResponse(['error' => 'Operation is not allowed.']);
        }

        $this->setAllowedProperties([
            'instanceId',
            'processId',
            'userId',
            'data',
            'currentOfficerId',
            'currentOfficerType',
            'status',
            'dateCreated',
            'dateModified',
            'description'
        ]);

        $results = $this->getResults([$this, 'getProcessInstances'], 'instanceId', $this->get('limit'), $this->get('offset'));

        $this->logRead(ExternalSystemLogObjectTypes::PROCESS_INSTANCES);

        return new JsonResponse(['data' => $results]);
    }

    protected function getProcessInstances(int $limit, int $offset): array {
        $qb = $this->container->processInstanceRepository->commonComposeQuery();

        $qb->andWhere($qb->getColumnInValues('status', [ProcessInstanceStatus::NEW, ProcessInstanceStatus::IN_PROGRESS]));

        $sqlForOfficer = "
            (
                (
                    currentOfficerType = " . ProcessInstanceOfficerTypes::USER . "
                        AND
                    currentOfficerId = '" . $this->userId . "'
                )
                    OR
                (
                    currentOfficerType = " . ProcessInstanceOfficerTypes::GROUP . "
                        AND
                    currentOfficerId IN (" . implode(', ', $this->getGroupIdsWhereUserIsMember()) . ")
                )
            )
        ";

        $qb->andWhere($sqlForOfficer)
            ->orderBy('dateCreated', 'DESC')
            ->execute();

        $results = [];
        while($row = $qb->fetchAssoc()) {
            $results[] = DatabaseRow::createFromDbRow($row);
        }

        return $results;
    }

    /**
     * Returns an array of IDs of groups current user is member of
     */
    private function getGroupIdsWhereUserIsMember(): array {
        $groupIds = [];

        $qb = $this->container->groupManager->composeQueryForGroupsWhereUserIsMember($this->userId);
        $qb->execute();

        while($row = $qb->fetchAssoc()) {
            $groupIds[] = '\'' . $row['groupId'] . '\'';
        }

        return $groupIds;
    }
}

?>