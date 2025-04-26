<?php

namespace App\Managers\Container;

use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ProcessRepository;

class ProcessManager extends AManager {
    public ProcessRepository $processRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, ProcessRepository $processRepository) {
        parent::__construct($logger, $entityManager);

        $this->processRepository = $processRepository;
    }

    /**
     * Inserts a new process from data array
     * 
     * @param array $data Data array
     * @throws GeneralException
     */
    public function insertNewProcessFromDataArray(array $data) {
        $processId = null;
        if(array_key_exists('processId', $data)) {
            $processId = $data['processId'];
        } else {
            $processId = $this->createId(EntityManager::C_PROCESSES);
        }

        if(!$this->processRepository->addNewProcess(
            $processId,
            $data['uniqueProcessId'],
            $data['title'],
            $data['description'],
            $data['form'],
            $data['userId'],
            $data['status'],
            $data['workflow'],
            $data['workflowConfiguration']
        )) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Returns a DatabaseRow instance for process by given $processId
     * 
     * @param string $processId Process ID
     */
    public function getProcessById(string $processId): DatabaseRow {
        $process = $this->processRepository->getProcessById($processId);

        if($process === null) {
            throw new GeneralException('No process \'' . $processId . '\' was found.');
        }

        return DatabaseRow::createFromDbRow($process);
    }
}

?>