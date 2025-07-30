<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Core\HashManager;
use App\Logger\Logger;
use PeeQL\Operations\QueryOperation;
use PeeQL\Result\QueryResult;
use QueryBuilder\QueryBuilder;

class TransactionLogRepository {
    public DatabaseConnection $db;
    private Logger $logger;

    public function __construct(DatabaseConnection $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    private function qb(string $method) {
        return new QueryBuilder($this->db, $this->logger, $method);
    }

    public function createNewEntry(?string $userId, string $methodName, string &$sql = '', ?string $containerId = null, ?string $dateCreated = null) {
        $qb = $this->qb(__METHOD__);

        $methodName = str_replace('\\', '\\\\', $methodName);

        $id = $this->getUniqueId();

        $keys = ['transactionId', 'callingMethod', 'userId'];
        $values = [$id, $methodName, $userId];

        if($containerId !== null) {
            $keys[] = 'containerId';
            $values[] = $containerId;
        }
        if($dateCreated !== null) {
            $keys[] = 'dateCreated';
            $values[] = $dateCreated;
        }

        $qb ->insert('transaction_log', $keys)
            ->values($values)
            ->execute();
        
        return $qb->fetchBool();
    }

    private function getUniqueId(): string {
        $unique = true;
        $run = true;
        $id = null;
        $x = 0;
        while($run) {
            $id = HashManager::createEntityId();

            $qb = $this->qb(__METHOD__);
            $qb->select(['COUNT(*) AS cnt'])
                ->from('transaction_log')
                ->where('transactionId = ?', [$id])
                ->execute();

            if($qb->fetch('cnt') == 0) {
                $unique = true;
            }

            if($unique || $x >= 100) {
                $run = false;
                break;
            }

            $x++;
        }

        return $id;
    }

    public function get(QueryOperation $operation): QueryResult {
        $qb = $this->qb(__METHOD__);

        $qb->select($operation->getColumns())
            ->from('transaction_log');

        $conditions = $operation->getConditions()->getConvertedConditionsAsArray();

        foreach($conditions as $condition) {
            $qb->andWhere($condition);
        }

        if($operation->getLimit() !== null) {
            $qb->limit($operation->getLimit());
        }

        if($operation->getPage() !== null) {
            $qb->offset($operation->getPage() - 1);
        }

        foreach($operation->getOrderBy() as $key => $order) {
            $qb->orderBy($key, $order);
        }

        $qb->execute();

        $qr = new QueryResult();
        $columns = $operation->getColumns();

        $data = [];
        $i = 0;
        while($row = $qb->fetchAssoc()) {
            foreach($columns as $column) {
                if(array_key_exists($column, $row)) {
                    $data[$i][$column] = $row[$column];
                }
            }
            $i++;
        }

        $qr->setResultData($data);

        return $qr;
    }

    public function composeQueryForTransactionLog(?string $containerId = null) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('transaction_log');

        if($containerId !== null) {
            $qb->andWhere('containerId = ?', [$containerId]);
        }

        return $qb;
    }

    public function getUserIdsInTransactionLog(?string $containerId = null) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['DISTINCT userId'])
            ->from('transaction_log');

        if($containerId !== null) {
            $qb->andWhere('containerId = ?', [$containerId]);
        }

        $qb->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = $row['userId'];
        }

        return $users;
    }
}

?>