<?php

namespace App\Managers;

use App\Core\Caching\CacheFactory;
use App\Core\DatabaseConnection;
use App\Core\HashManager;
use App\Exceptions\GeneralException;
use App\Logger\Logger;

/**
 * Abstract class AManager must extend all managers in the \App\Managers namespace.
 * 
 * @author Lukas Velek
 */
abstract class AManager {
    protected Logger $logger;
    public ?EntityManager $entityManager;
    protected CacheFactory $cacheFactory;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     * @param null|EntityManager $entityManager Entity manager or null
     */
    protected function __construct(Logger $logger, ?EntityManager $entityManager) {
        $this->inject($logger, $entityManager);
    }

    /**
     * Injects same parameters as constructor
     * 
     * @param Logger $logger Logger instance
     * @param null|EntityManager $entityManager Entity manager or null
     */
    public function inject(Logger $logger, ?EntityManager $entityManager) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        
        $this->startup();
    }

    /**
     * Sets up the object
     */
    protected function startup() {
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
     * Generates a unique entity ID for given entity category (users, posts, topics, ...)
     * 
     * @param string $category Category (use contants in \App\Managers\EntityManager)
     * @return string|null Generated unique entity ID or null
     */
    public function createId(string $category) {
        if($this->entityManager !== null) {
            return $this->entityManager->generateEntityId($category);
        }

        return null;
    }
    /**
     * Generates a unique entity ID for given entity category (users, posts, topics, ...)
     * for a custom database
     * 
     * @param string $category Category (use contants in \App\Managers\EntityManager)
     * @param DatabaseConnection $conn Database connection to custom database
     * @return string|null Generated unique entity ID or null
     */
    public function createIdCustomDb(string $category, DatabaseConnection $conn) {
        if($this->entityManager !== null) {
            return $this->entityManager->generateEntityIdCustomDb($category, $conn);
        }

        return null;
    }

    /**
     * Generates a unique hash for column in given table
     * 
     * @param int $hashLength Hash length
     * @param string $category Table name
     * @param string $columnName Column name
     */
    public function createUniqueHashForDb(int $hashLength, string $category, string $columnName): string {
        if($this->entityManager === null) {
            throw new GeneralException('EntityManager is not set.');
        }

        $run = true;

        $hash = null;
        do {
            $hash = HashManager::createHash($hashLength, false);

            if($this->entityManager->checkUniqueValueInColumn($category, $columnName, $hash)) {
                $run = false;
            }
        } while($run);

        return $hash;
    }
}

?>