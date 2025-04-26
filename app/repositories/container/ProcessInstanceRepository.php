<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;
use QueryBuilder\QueryBuilder;

/**
 * ProcessInstanceRepository contains low-level database operations for process instances
 * 
 * @author Lukas Velek
 */
class ProcessInstanceRepository extends ARepository {
    /**
     * Composes a common QueryBuilder instance
     */
    public function commonComposeQuery(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_instances');

        return $qb;
    }

    /**
     * Inserts a new process instance
     * 
     * @param array $data Process instance data
     */
    public function insertNewProcessInstance(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $keys = array_keys($data);
        $values = array_values($data);

        $qb->insert('process_instances', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Updates process instance
     * 
     * @param string $instanceId Instance ID
     * @param array $data Process instance data
     */
    public function updateProcessInstance(string $instanceId, array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->update('process_instances')
            ->set($data)
            ->where('instanceId = ?', [$instanceId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>