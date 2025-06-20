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
     * Returns last process for unique process ID
     * 
     * @param string $uniqueProcessId Unique process ID
     * @throws GeneralException
     */
    public function getLastProcessForUniqueProcessId(string $uniqueProcessId): DatabaseRow {
        $qb = $this->processRepository->commonComposeQuery();

        $qb->andWhere('uniqueProcessId = ?', [$uniqueProcessId])
            ->andWhere('status = 1')
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
}

?>