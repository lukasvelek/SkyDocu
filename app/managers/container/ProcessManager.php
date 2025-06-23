<?php

namespace App\Managers\Container;

use App\Constants\Container\ProcessStatus;
use App\Core\DB\DatabaseRow;
use App\Entities\ContainerProcessEntity;
use App\Exceptions\AException;
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
            $data['definition'],
            $data['userId'],
            $data['status']
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

    /**
     * Returns a ContainerProcessEntity instance for process given by $processId
     * 
     * @param string $processId Process ID
     * @throws GeneralException
     */
    public function getProcessEntityById(string $processId): ContainerProcessEntity {
        $process = $this->processRepository->getProcessById($processId);

        if($process === null) {
            throw new GeneralException('No process \'' . $processId . '\' was found.');
        }

        return ContainerProcessEntity::createEntityFromDbRow($process);
    }

    /**
     * Returns last process for unique process ID
     * 
     * @param string $uniqueProcessId Unique process ID
     * @throws GeneralException
     */
    public function getLastProcessForUniqueProcessId(string $uniqueProcessId): DatabaseRow {
        $qb = $this->processRepository->commonComposeQuery();

        $qb->andWhere('uniqueProcessId = ?', [$uniqueProcessId])
            ->andWhere('status IN (1,4)') // in distribution or if custom then current
            ->orderBy('dateCreated', 'DESC')
            ->limit(1)
            ->execute();
        
        $result = $qb->fetch();

        if($result === null) {
            throw new GeneralException('No process for unique process ID \'' . $uniqueProcessId . '\' exists.');
        }

        return DatabaseRow::createFromDbRow($result);
    }

    /**
     * Returns unique process ID for given process ID
     * 
     * @param string $processId Process ID
     */
    public function getUniqueProcessIdForProcessId(string $processId): string {
        $process = $this->getProcessById($processId);

        return $process->uniqueProcessId;
    }

    /**
     * Enables process by unique process ID
     * 
     * @param string $uniqueProcessId Unique process ID
     */
    public function enableProcessByUniqueProcessId(string $uniqueProcessId) {
        $process = $this->getLastProcessForUniqueProcessId($uniqueProcessId);

        $data = [
            'isEnabled' => 1
        ];

        if(!$this->processRepository->updateProcess($process->processId, $data)) {
            throw new GeneralException('Database error');
        }
    }

    /**
     * Disables process by unique process ID
     * 
     * @param string $uniqueProcessId Unique process ID
     */
    public function disableProcessByUniqueProcessId(string $uniqueProcessId) {
        $process = $this->getLastProcessForUniqueProcessId($uniqueProcessId);

        $data = [
            'isEnabled' => 0
        ];

        if(!$this->processRepository->updateProcess($process->processId, $data)) {
            throw new GeneralException('Database error');
        }
    }

    /**
     * Updates process
     * 
     * @param string $processId Process ID
     * @param array $data Data array
     */
    public function updateProcess(string $processId, array $data) {
        if(!$this->processRepository->updateProcess($processId, $data)) {
            throw new GeneralException('Database error');
        }
    }

    /**
     * Creates a new process and returns a new process ID and unique process ID
     * 
     * @param string $title TItle
     * @param string $description Description
     * @param string $authorId Author user ID
     * @param array $definition Definition
     * @param ?string $oldProcessId Old process ID
     */
    public function createNewProcess(
        string $title,
        string $description,
        string $authorId,
        array $definition,
        ?string $oldProcessId
    ) {
        $processId = $this->createId(EntityManager::C_PROCESSES);

        $version = 1;
        $uniqueProcessId = null;
        if($oldProcessId !== null) {
            $process = $this->getProcessById($oldProcessId);

            $uniqueProcessId = $process->uniqueProcessId;
            $version = (int)($this->getHighestVersionForUniqueProcessId($uniqueProcessId)) + 1;
        } else {
            $uniqueProcessId = $this->createId(EntityManager::C_PROCESSES_UNIQUE);
        }

        $data = [
            'processId' => $processId,
            'uniqueProcessId' => $uniqueProcessId,
            'title' => $title,
            'description' => $description,
            'userId' => $authorId,
            'definition' => base64_encode(json_encode($definition)),
            'status' => ProcessStatus::NEW,
            'version' => $version
        ];

        if(!$this->processRepository->addNewProcessFromArray($data)) {
            throw new GeneralException('Database error');
        }

        return [$processId, $uniqueProcessId];
    }

    /**
     * Returns the highest version used for unique process ID
     * 
     * @param string $uniqueProcessId Unique process ID
     */
    public function getHighestVersionForUniqueProcessId(string $uniqueProcessId): string {
        $qb = $this->processRepository->commonComposeQuery();
        $qb->andWhere('uniqueProcessId = ?', [$uniqueProcessId])
            ->orderBy('version', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch('version');
    }

    /**
     * Returns true if the process is custom or false if it is in distribution
     * 
     * @param string $processId Process ID
     */
    public function isProcessCustom(string $processId): bool {
        $process = $this->getProcessEntityById($processId);

        return ($process->getVersion() !== null);
    }

    /**
     * Returns previous version for process ID
     * 
     * @param string $processId Process ID
     * @param bool $returnEntity True if ProcessEntity should be returned or false if DatabaseRow should be returned
     * @throws AException
     */
    public function getPreviousVersionForProcessId(string $processId, bool $returnEntity = false): null|DatabaseRow|ContainerProcessEntity {
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

    /**
     * Returns next version for process ID
     * 
     * @param string $processId Process ID
     * @param bool $returnEntity True if ProcessEntity should be returned or false if DatabaseRow should be returned
     * @throws AException
     */
    public function getNextVersionForProcessId(string $processId, bool $returnEntity = false): null|DatabaseRow|ContainerProcessEntity {
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
     * Returns a process by unique process ID and version
     * 
     * @param string $uniqueProcessId Unique process ID
     * @param int $version Version
     * @throws GeneralException
     */
    public function getProcessByUniqueProcessIdAndVersion(string $uniqueProcessId, int $version): DatabaseRow {
        $qb = $this->processRepository->commonComposeQuery();
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
            $oldProcessId
        );

        return [$newProcessId, $uniqueProcessId];
    }
}

?>