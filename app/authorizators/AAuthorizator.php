<?php

namespace App\Authorizators;

use App\Core\Caching\CacheFactory;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use QueryBuilder\ExpressionBuilder;
use QueryBuilder\QueryBuilder;

/**
 * Abstract class AAuthorizator that contains common methods used in other extending authorizators.
 * 
 * @author Lukas Velek
 */
abstract class AAuthorizator {
    private DatabaseConnection $db;
    protected Logger $logger;
    protected CacheFactory $cacheFactory;

    /**
     * Abstract class constructor
     * 
     * @param DatabaseConnection $db DatabaseConnection instance
     * @param Logger $logger Logger instance
     */
    protected function __construct(DatabaseConnection $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
        $this->cacheFactory = new CacheFactory();
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
     * @param string $method Name of the calling method
     * @return QueryBuilder QueryBuilder instance
     */
    protected function qb(string $method = __METHOD__) {
        return new QueryBuilder($this->db, $this->logger, $method);
    }
    
    /**
     * Returns a new instance of ExpressionBuilder
     * 
     * @return ExpressionBuilder ExpressionBuilder instance
     */
    protected function xb() {
        return new ExpressionBuilder();
    }
}

?>