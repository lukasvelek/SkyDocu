<?php

namespace App\Authorizators;

use App\Core\Caching\CacheFactory;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;
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
    protected UserRepository $userRepository;
    private CacheFactory $cacheFactory;

    /**
     * Abstract class constructor
     * 
     * @param DatabaseConnection $db DatabaseConnection instance
     * @param Logger $logger Logger instance
     * @param GroupRepository $groupRepository GroupRepository instance
     * @param UserRepository $userRepository UserRepository instance
     */
    protected function __construct(DatabaseConnection $db, Logger $logger, UserRepository $userRepository) {
        $this->db = $db;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->cacheFactory = new CacheFactory();
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