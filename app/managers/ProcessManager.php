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
     */
    public function createNewProcess(string $title, string $description, string $authorId, string $formCode) {
        $processId = $this->createId(EntityManager::PROCESSES);

        if(!$this->processRepository->insertNewProcess($processId, $title, $description, $formCode, $authorId, ProcessStatus::NEW)) {
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