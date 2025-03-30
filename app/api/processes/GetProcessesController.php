<?php

namespace App\Api\Processes;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;

class GetProcessesController extends AReadAPIOperation {
    protected function run(): JsonResponse {
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

        $results = [];
        $properties = $this->processPropeties($this->get('properties'));

        $processes = $this->getProcesses($this->get('limit'), $this->get('offset'));

        foreach($processes as $process) {
            foreach($properties as $property) {
                $results[$process->processId][$property] = $process->$property;
            }
        }

        $this->logRead(ExternalSystemLogObjectTypes::PROCESS);

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Returns an array of processes
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    private function getProcesses(int $limit, int $offset): array {
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