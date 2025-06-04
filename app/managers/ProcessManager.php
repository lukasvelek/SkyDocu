<?php

namespace App\Managers;

use App\Constants\ProcessStatus;
use App\Core\DB\DatabaseRow;
use App\Entities\ProcessEntity;
use App\Exceptions\AException;
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
     * Creates a new process from existing one - creates a copy and returns the new process' ID
     * 
     * @param string $oldProcessId Old process ID
     */
    public function createNewProcessFromExisting(string $oldProcessId): array {
        $process = $this->getProcessById($oldProcessId);

        [$newProcessId, $uniqueProcessId] = $this->createNewProcess(
            $process->title,
            $process->description,
            $process->userId,
            json_decode(base64_decode($process->definition), true),
            $oldProcessId,
            ProcessStatus::NEW
        );

        $this->updateProcess($newProcessId, [
            'metadataDefinition' => $process->metadataDefinition
        ]);

        return [$newProcessId, $uniqueProcessId];
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

            $uniqueProcessId = $process->uniqueProcessId;
            $version = (int)($this->getHighestVersionForUniqueProcessId($uniqueProcessId)) + 1;
        } else {
            $uniqueProcessId = $this->createId(EntityManager::PROCESSES_UNIQUE);
        }

        if(!$this->processRepository->insertNewProcess($processId, $uniqueProcessId, $title, $description, base64_encode(json_encode($definition)), $authorId, $status, $version)) {
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

    /**
     * Returns a process by unique process ID and version
     * 
     * @param string $uniqueProcessId Unique process ID
     * @param int $version Version
     * @throws GeneralException
     */
    public function getProcessByUniqueProcessIdAndVersion(string $uniqueProcessId, int $version): DatabaseRow {
        $qb = $this->processRepository->composeQueryForProcesses();
        $qb->andWhere('uniqueProcessId = ?', [$uniqueProcessId])
            ->andWhere('version = ?', [$version])
            ->execute();

        $result = $qb->fetch();

        if($result === null) {
            throw new GeneralException('No process found for unique process ID and version.');
        }

        return DatabaseRow::createFromDbRow($result);
    }

    /**
     * Returns previous version for process ID
     * 
     * @param string $processId Process ID
     * @param bool $returnEntity True if ProcessEntity should be returned or false if DatabaseRow should be returned
     * @throws AException
     */
    public function getPreviousVersionForProcessId(string $processId, bool $returnEntity = false): null|DatabaseRow|ProcessEntity {
        try {
            $process = $this->getProcessEntityById($processId);

            if($process->getVersion() > 1) {
                $uniqueProcessId = $process->getUniqueProcessId();

                $previousVersion = $this->getProcessByUniqueProcessIdAndVersion($uniqueProcessId, $process->getVersion() - 1);

                if($returnEntity) {
                    return $this->getProcessEntityById($previousVersion->processId);
                }

                return $previousVersion;
            }

            return null;
        } catch(AException $e) {
            throw $e;
        }
    }

    public function getPreviousVersionInDistributionForProcessId(string $processId, bool $returnEntity = false): null|DatabaseRow|ProcessEntity {
        try {
            $process = $this->getProcessEntityById($processId);

            if($process->getVersion() > 1) {
                $previousVersion = null;
                $run = true;

                while($run) {
                    try {
                        $previousVersion = $this->getPreviousVersionForProcessId($processId, true);

                        if($previousVersion->getStatus() == ProcessStatus::IN_DISTRIBUTION) {
                            $run = false;
                        }
                    } catch(AException $e) {
                        $run = false;
                        $previousVersion = null;
                    }
                }

                return $previousVersion;
            }

            return null;
        } catch(AException $e) {
            throw $e;
        }
    }

    /**
     * Returns next version for process ID
     * 
     * @param string $processId Process ID
     * @param bool $returnEntity True if ProcessEntity should be returned or false if DatabaseRow should be returned
     * @throws AException
     */
    public function getNextVersionForProcessId(string $processId, bool $returnEntity = false): null|DatabaseRow|ProcessEntity {
        try {
            $process = $this->getProcessEntityById($processId);

            if($process->getVersion() > 1) {
                $uniqueProcessId = $process->getUniqueProcessId();

                $previousVersion = $this->getProcessByUniqueProcessIdAndVersion($uniqueProcessId, $process->getVersion() + 1);

                if($returnEntity) {
                    return $this->getProcessEntityById($previousVersion->processId);
                }

                return $previousVersion;
            }

            return null;
        } catch(AException $e) {
            throw $e;
        }
    }

    /**
     * Returns the highest version used for unique process ID
     * 
     * @param string $uniqueProcessId Unique process ID
     */
    public function getHighestVersionForUniqueProcessId(string $uniqueProcessId): string {
        $qb = $this->processRepository->composeQueryForProcesses();
        $qb->andWhere('uniqueProcessId = ?', [$uniqueProcessId])
            ->orderBy('version', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch('version');
    }
}

?>