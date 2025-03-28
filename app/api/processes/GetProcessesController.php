<?php

namespace App\Api\Processes;

use App\Api\AAuthenticatedApiController;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;
use App\Repositories\Container\ProcessRepository;

class GetProcessesController extends AAuthenticatedApiController {
    protected function run(): JsonResponse {
        $results = [];
        $properties = $this->get('properties');

        if(array_key_exists('processId', $this->data)) {
            // single

            $process = $this->getProcess($this->get('processId'));

            foreach($properties as $property) {
                $results[$property] = $process->$property;
            }
        } else {
            $processes = $this->getProcesses($this->get('limit'), $this->get('offset'));

            foreach($processes as $process) {
                foreach($properties as $property) {
                    $results[$process->processId][$property] = $process->$property;
                }
            }
        }

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Returns an array of processes
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    private function getProcesses(int $limit, int $offset): array {
        $processRepository = new ProcessRepository($this->conn, $this->app->logger);

        $qb = $processRepository->commonComposeQuery(false)
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
        $processRepository = new ProcessRepository($this->conn, $this->app->logger);

        $process = $processRepository->getProcessById($processId);

        if($process === null) {
            throw new GeneralException('Process does not exist.');
        }

        return DatabaseRow::createFromDbRow($process);
    }
}

?>