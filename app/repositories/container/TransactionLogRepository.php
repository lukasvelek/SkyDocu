<?php

namespace App\Repositories\Container;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\ARepository;

class TransactionLogRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function composeQueryForTransactionLog() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('transaction_log');

        return $qb;
    }

    public function getUserIdsInTransactionLog() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['userId'])
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