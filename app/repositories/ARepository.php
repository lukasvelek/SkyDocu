<?php

namespace App\Repositories;

use App\Core\Caching\CacheFactory;
use App\Core\DatabaseConnection;
use App\Core\DB\AMultipleDatabaseConnectionHandler;
use App\Exceptions\DatabaseExecutionException;
use App\Logger\Logger;
use App\Managers\EntityManager;
use PeeQL\Operations\QueryOperation;
use PeeQL\Result\QueryResult;
use QueryBuilder\ExpressionBuilder;
use QueryBuilder\QueryBuilder;

/**
 * Common class for all repositories
 * 
 * @author Lukas Velek
 */
abstract class ARepository extends AMultipleDatabaseConnectionHandler {
    protected Logger $logger;
    public TransactionLogRepository $transactionLogRepository;
    protected CacheFactory $cacheFactory;
    private ?string $containerId;
    protected ?string $userId;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $conn Database connection instance
     * @param Logger $logger Logger instance
     */
    public function __construct(DatabaseConnection $conn, Logger $logger, TransactionLogRepository $transactionLogRepository, ?string $userId = null) {
        parent::__construct($conn);

        $this->logger = $logger;
        $this->transactionLogRepository = $transactionLogRepository;
        $this->userId = $userId;

        $this->containerId = null;
    }

    /**
     * Sets current user ID
     * 
     * @param string $userId User ID
     */
    public function setUserId(string $userId) {
        $this->userId = $userId;
    }

    /**
     * Sets container ID
     * 
     * @param string $containerId Container ID
     */
    public function setContainerId(string $containerId) {
        $this->containerId = $containerId;
    }

    /**
     * Injects custom CacheFactory instance
     * 
     * @param CacheFactory $cacheFactory CacheFactory intstance
     */
    public function injectCacheFactory(CacheFactory $cacheFactory) {
        $this->cacheFactory = $cacheFactory;
    }

    /**
     * Returns a new instance of QueryBuilder
     * 
     * @param string $method Method name
     * @return QueryBuilder New QueryBuilder instance
     */
    protected function qb(string $method = __METHOD__) {
        return new QueryBuilder($this->conn, $this->logger, $method);
    }

    /**
     * Returns a new instance of ExpressionBuilder
     * 
     * @return ExpressionBuilder New ExpressionBuilder instance
     */
    protected function xb() {
        return new ExpressionBuilder();
    }

    /**
     * Begins a database transaction
     * 
     * @param ?string $method Method name
     * @return bool True on success or false on failure
     */
    public function beginTransaction(?string $method = null) {
        $result = $this->conn->beginTransaction();
        if($result) {
            $this->logger->warning('Transaction begun.', $method ?? __METHOD__);
        }
        return $result;
    }

    /**
     * Rolls back current database transaction
     * 
     * @param ?string $method Method name
     * @return bool True on success or false on failure
     */
    public function rollback(?string $method = null) {
        $result = $this->conn->rollback();
        if($result) {
            $this->logger->warning('Transaction rolled back.', $method ?? __METHOD__);
        }
        return $result;
    }

    /**
     * Commits current database transaction
     * 
     * @param ?string $userId Calling user ID
     * @param string $method Method name
     * @return bool True on success or false on failure
     */
    public function commit(?string $userId, string $method) {
        $result = $this->conn->commit();
        if($result) {
            $sql = '';
            if(!$this->logTransaction($userId, $method, $sql, $this->containerId)) {
                $this->rollback();
                throw new DatabaseExecutionException('Could not log transcation. Rolling back.', $sql);
            }
            $this->logger->warning('Transaction commited.', __METHOD__);
        }
        return $result;
    }

    /**
     * Executes given SQL query
     * 
     * @param string $sql SQL query
     * @return mixed SQL query result
     */
    public function executeSql(string $sql) {
        $this->logger->sql($sql, __METHOD__, null);
        return $this->conn->query($sql);
    }

    /**
     * Returns instance of QueryBuilder
     * 
     * @param string $method Calling method
     * @return QueryBuilder
     */
    public function getQb(string $method = __METHOD__) {
        return $this->qb($method);
    }

    /**
     * Returns current instance of Logger
     * 
     * @return Logger
     */
    public function getLogger() {
        return $this->logger;
    }

    /**
     * Applies limit and offset to the QueryBuilder
     * 
     * @param QueryBuilder &$qb
     * @param int $limit
     * @param int $offset
     */
    protected function applyGridValuesToQb(QueryBuilder &$qb, int $limit, int $offset) {
        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }
    }

    /**
     * Logs transaction to the database
     * 
     * @param ?string $userId
     * @param string $method Calling method's name
     * @param string &$sql SQL query
     * 
     * @return bool True if successful or false if not
     */
    private function logTransaction(?string $userId, string $method, string &$sql, ?string $containerId = null) {
        $transactionId = $this->createEntityId(EntityManager::TRANSACTIONS);

        return $this->transactionLogRepository->createNewEntry($transactionId, $userId, $method, $sql, $containerId);
    }

    /**
     * Creates unique entity ID
     * 
     * @param string $category Entity category (EntityManager constants)
     * @return ?string Entity ID or null
     */
    public function createEntityId(string $category) {
        $em = new EntityManager($this->logger, new ContentRepository($this->conn, $this->logger, $this->transactionLogRepository));

        return $em->generateEntityId($category);
    }

    /**
     * Deletes entry in the database by given key value pair
     * 
     * @param string $tableName
     * @param string $keyName
     * @param string $keyValue
     * @return bool True if successful or false if not
     */
    protected function deleteEntryById(string $tableName, string $keyName, string $keyValue) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from($tableName)
            ->where($keyName . ' = ?', [$keyValue])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Returns a single row by given key value pair
     * 
     * @param string $tableName
     * @param string $keyName
     * @param string $keyValue
     * @return mixed SQL query result
     */
    protected function getRow(string $tableName, string $keyName, string $keyValue) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from($tableName)
            ->where($keyName . ' = ?', [$keyValue])
            ->execute();

        return $qb->fetch();
    }

    /**
     * Processes PeeQL into a QueryBuilder instance, executes and fetches the data
     * 
     * @param string $tableName Table name
     * @param QueryOperation $operation QueryOperation instance
     */
    protected function processPeeQL(string $tableName, QueryOperation $operation): QueryResult {
        $qb = $this->qb(__METHOD__);

        $qb->select($operation->getColumns())
            ->from($tableName);

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
}

?>