<?php

namespace App\Managers;

use App\Constants\ProcessStatus;
use App\Core\DB\DatabaseRow;
use App\Entities\ProcessEntity;
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
    public ProcessRepository $processRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, ProcessRepository $processRepository) {
        parent::__construct($logger, $entityManager);

        $this->processRepository = $processRepository;
    }

    /**
     * Creates a new process and returns a new process ID
     * 
     * @param string $title Title
     * @param string $description Description
     * @param string $authorId Author user ID
     * @param array $definition Form definition
     * @param ?string $oldProcessId Old process ID
     */
    public function createNewProcess(string $title, string $description, string $authorId, array $definition, ?string $oldProcessId = null, int $status = ProcessStatus::IN_DISTRIBUTION): array {
        $processId = $this->createId(EntityManager::PROCESSES);

        $version = 1;
        $uniqueProcessId = null;
        if($oldProcessId !== null) {
            $process = $this->getProcessById($oldProcessId);

            $version = (int)($process->version) + 1;
            $uniqueProcessId = $process->uniqueProcessId;
        } else {
            $uniqueProcessId = $this->createId(EntityManager::PROCESSES_UNIQUE);
        }

        if(!$this->processRepository->insertNewProcess($processId, $uniqueProcessId, $title, $description, json_encode($definition), $authorId, $status, $version)) {
            throw new GeneralException('Database error.');
        }

        return [$processId, $uniqueProcessId];
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

    /**
     * Returns a process entity for process ID
     * 
     * @param string $processId Process ID
     */
    public function getProcessEntityById(string $processId): ProcessEntity {
        $process = $this->processRepository->getProcessById($processId);

        if($process === null) {
            throw new NonExistingEntityException('Process does not exist');
        }

        return ProcessEntity::createEntityFromDbRow($process);
    }

    /**
     * Updates process
     * 
     * @param string $processId Process ID
     * @param array $data Data
     */
    public function updateProcess(string $processId, array $data) {
        if(!$this->processRepository->updateProcess($processId, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Deletes process
     * 
     * @param string $processId Process ID
     */
    public function deleteProcess(string $processId) {
        if(!$this->processRepository->deleteProcess($processId)) {
            throw new GeneralException('Database error.');
        }
    }
}

?>