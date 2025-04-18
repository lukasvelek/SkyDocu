<?php

namespace App\Repositories;

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
     * Inserts a new process
     * 
     * @param string $processId Process ID
     * @param string $uniqueProcessId Unique process ID
     * @param string $title Title
     * @param string $description Description
     * @param string $form Form code
     * @param string $userId User ID
     * @param int $status Status
     * @param int $version Version
     */
    public function insertNewProcess(string $processId, string $uniqueProcessId, string $title, string $description, string $form, string $userId, int $status, int $version): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('processes', ['processId', 'uniqueProcessId', 'title', 'description', 'form', 'userId', 'status', 'version'])
            ->values([$processId, $uniqueProcessId, $title, $description, $form, $userId, $status, $version])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Returns a process by ID
     * 
     * @param string $processId Process ID
     */
    public function getProcessById(string $processId) {
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
    public function composeQueryForProcessType(string $uniqueProcessId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('processes')
            ->where('uniqueProcessId = ?', [$uniqueProcessId]);

        return $qb;
    }
}

?>