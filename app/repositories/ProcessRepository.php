<?php

namespace App\Repositories;

use App\Constants\ProcessStatus;
use QueryBuilder\QueryBuilder;

/**
 * ProcessRepository contains low-level database operations for processes
 * 
 * @author Lukas Velek
 */
class ProcessRepository extends ARepository {
    /**
     * Composes an instance of QueryBuilder for processes
     */
    public function composeQueryForProcesses(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('processes');

        return $qb;
    }

    /**
     * Composes an instance of QueryBuilder for processes in distribution
     */
    public function composeQueryForProcessesInDistribution(): QueryBuilder {
        $qb = $this->composeQueryForProcesses();

        $qb->andWhere('status = ?', [ProcessStatus::IN_DISTRIBUTION]);

        return $qb;
    }

    /**
     * Inserts a new process
     * 
     * @param string $processId Process ID
     * @param string $uniqueProcessId Unique process ID
     * @param string $title Title
     * @param string $description Description
     * @param string $definition Definition
     * @param string $userId User ID
     * @param int $status Status
     * @param int $version Version
     * @param string $name Name
     */
    public function insertNewProcess(string $processId, string $uniqueProcessId, string $title, string $description, string $definition, string $userId, int $status, int $version, string $name): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('processes', ['processId', 'uniqueProcessId', 'title', 'description', 'definition', 'userId', 'status', 'version', 'name'])
            ->values([$processId, $uniqueProcessId, $title, $description, $definition, $userId, $status, $version, $name])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Returns a process by ID
     * 
     * @param string $processId Process ID
     */
    public function getProcessById(string $processId): mixed {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('processes')
            ->where('processId = ?', [$processId])
            ->execute();

        return $qb->fetch();
    }

    /**
     * Composes a QueryBuilder instance for processes of a type
     * 
     * @param string $uniqueProcessId Unique process ID
     */
    public function composeQueryForProcessType(string $uniqueProcessId): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('processes')
            ->where('uniqueProcessId = ?', [$uniqueProcessId]);

        return $qb;
    }

    /**
     * Updates process
     * 
     * @param string $processId Process ID
     * @param array $data Data
     */
    public function updateProcess(string $processId, array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->update('processes')
            ->set($data)
            ->where('processId = ?', [$processId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Deletes process
     * 
     * @param string $processId Process ID
     */
    public function deleteProcess(string $processId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('processes')
            ->where('processId = ?', [$processId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>