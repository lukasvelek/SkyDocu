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
}

?>