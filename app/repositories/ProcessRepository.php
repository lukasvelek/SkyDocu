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
     * @param string $title Title
     * @param string $description Description
     * @param string $form Form code
     * @param string $userId User ID
     * @param int $status Status
     */
    public function insertNewProcess(string $processId, string $title, string $description, string $form, string $userId, int $status): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('processes', ['processId', 'title', 'description', 'form', 'userId', 'status'])
            ->values([$processId, $title, $description, $form, $userId, $status])
            ->execute();

        return $qb->fetchBool();
    }
}

?>