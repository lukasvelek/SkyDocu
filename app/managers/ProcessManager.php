<?php

namespace App\Managers;

use App\Constants\ProcessStatus;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\ProcessRepository;

/**
 * ProcessManager contains high-level database operations for processes
 * 
 * @author Lukas Velek
 */
class ProcessManager extends AManager {
    private ProcessRepository $processRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, ProcessRepository $processRepository) {
        parent::__construct($logger, $entityManager);

        $this->processRepository = $processRepository;
    }

    /**
     * Creates a new process
     * 
     * @param string $title Title
     * @param string $description Description
     * @param string $authorId Author user ID
     * @param string $formCode Form code
     * @param ?string $oldProcessId Old process ID
     */
    public function createNewProcess(string $title, string $description, string $authorId, string $formCode, ?string $oldProcessId = null) {
        $processId = $this->createId(EntityManager::PROCESSES);

        $version = 1;
        $uniqueProcessId = null;
        if($oldProcessId !== null) {
            $process = $this->getProcessById($oldProcessId);

            $version = (int)$process->version + 1;
            $uniqueProcessId = $process->uniqueProcessId;
        } else {
            $uniqueProcessId = $this->createId(EntityManager::PROCESSES_UNIQUE);
        }

        if(!$this->processRepository->insertNewProcess($processId, $uniqueProcessId, $title, $description, $formCode, $authorId, ProcessStatus::NEW, $version)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Returns a process row by ID
     * 
     * @param string $processId
     */
    public function getProcessById(string $processId): DatabaseRow {
        $process = $this->processRepository->getProcessById($processId);

        if($process === null) {
            throw new NonExistingEntityException('Process does not exist.');
        }

        return DatabaseRow::createFromDbRow($process);
    }
}

?>