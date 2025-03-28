<?php

namespace App\Api\Processes;

use App\Api\AAuthenticatedApiController;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;

class GetProcessesController extends AAuthenticatedApiController {
    protected function run(): JsonResponse {
        $results = [];
        $properties = $this->get('properties');

        if(array_key_exists('processId', $this->data)) {
            // single

            $process = $this->getProcess($this->get('processId'));

            foreach($properties as $property) {
                if(!$this->checkProperty($property)) continue;

                $results[$property] = $process->$property;
            }

            $this->logRead(false, ExternalSystemLogObjectTypes::PROCESS);
        } else {
            $processes = $this->getProcesses($this->get('limit'), $this->get('offset'));

            foreach($processes as $process) {
                foreach($properties as $property) {
                    if(!$this->checkProperty($property)) continue;
                    
                    $results[$process->processId][$property] = $process->$property;
                }
            }

            $this->logRead(true, ExternalSystemLogObjectTypes::PROCESS);
        }

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Checks if property is enabled
     * 
     * @param string $name Property name
     */
    private function checkProperty(string $name): bool {
        return in_array($name, [
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
    }

    /**
     * Returns an array of processes
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    private function getProcesses(int $limit, int $offset): array {
        $qb = $this->container->processRepository->commonComposeQuery(false)
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = DatabaseRow::createFromDbRow($row);
        }

        return $processes;
    }

    /**
     * Returns a process
     * 
     * @param string $processId Process ID
     */
    private function getProcess(string $processId): DatabaseRow {
        $process = $this->container->processRepository->getProcessById($processId);

        if($process === null) {
            throw new GeneralException('Process does not exist.');
        }

        return DatabaseRow::createFromDbRow($process);
    }
}

?>