<?php

namespace App\Api\Processes;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\Container\ExternalSystemRightsOperations;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetProcessesController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        if(!$this->checkRight(ExternalSystemRightsOperations::READ_PROCESSES)) {
            return new JsonResponse(['error' => 'Operation is not allowed.']);
        }

        $this->setAllowedProperties([
            'processId',
            'documentId',
            'type',
            'authorUserId',
            'currentOfficerUserId',
            'workflowUserIds',
            'dateCreated',
            'status',
            'currentOfficerSubstituteUserId'
        ]);

        $results = $this->getResults([$this, 'getProcesses'], 'processId', $this->get('limit'), $this->get('offset'));

        $this->logRead(ExternalSystemLogObjectTypes::PROCESS);

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Returns an array of processes
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    protected function getProcesses(int $limit, int $offset): array {
        $qb = $this->container->processRepository->commonComposeQuery(false);

        $this->appendWhereConditions($qb);

        $qb->limit($limit)
            ->offset($offset)
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = DatabaseRow::createFromDbRow($row);
        }

        return $processes;
    }
}

?>