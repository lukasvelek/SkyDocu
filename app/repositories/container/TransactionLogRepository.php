<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class TransactionLogRepository extends ARepository {
    public function composeQueryForTransactionLog() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('transaction_log');

        return $qb;
    }

    public function getUserIdsInTransactionLog() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['DISTINCT userId'])
            ->from('transaction_log')
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = $row['userId'];
        }

        return $users;
    }
}

?>