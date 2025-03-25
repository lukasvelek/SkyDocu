<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class ExternalSystemLogRepository extends ARepository {
    public function composeQueryForExternalSystemLog() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_system_log');

        return $qb;
    }
}

?>