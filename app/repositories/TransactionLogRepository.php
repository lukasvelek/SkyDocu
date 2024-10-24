<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use QueryBuilder\QueryBuilder;

class TransactionLogRepository {
    private DatabaseConnection $db;
    private Logger $logger;

    public function __construct(DatabaseConnection $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    private function qb(string $method) {
        return new QueryBuilder($this->db, $this->logger, $method);
    }

    public function createNewEntry(string $id, string $userId, string $methodName, string &$sql) {
        $qb = $this->qb(__METHOD__);

        $methodName = str_replace('\\', '\\\\', $methodName);

        $keys = ['transactionId', 'callingMethod', 'userId'];
        $values = [$id, $methodName, $userId];

        $qb ->insert('transaction_log', $keys)
            ->values($values)
            ->execute();

        $sql = $qb->getSQL();
        
        return $qb->fetchBool();
    }
}

?>