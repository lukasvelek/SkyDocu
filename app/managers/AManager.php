<?php

namespace App\Managers;

use App\Core\Caching\CacheFactory;
use App\Core\GUID;
use App\Logger\Logger;

/**
 * Abstract class AManager must extend all managers in the \App\Managers namespace.
 * 
 * @author Lukas Velek
 */
abstract class AManager {
    protected Logger $logger;
    protected CacheFactory $cacheFactory;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     */
    protected function __construct(Logger $logger) {
        $this->inject($logger);
    }

    /**
     * Injects same parameters as constructor
     * 
     * @param Logger $logger Logger instance
     */
    public function inject(Logger $logger) {
        $this->logger = $logger;
        
        $this->startup();
    }

    /**
     * Sets up the object
     */
    protected function startup() {
        //$this->cacheFactory = new CacheFactory();
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
     * Returns a newly generated GUID
     * 
     * @return string Newly generated GUID
     */
    public function createId() {
        return GUID::generate();
    }

    /**
     * Returns an array filled with generated GUIDs
     * 
     * @param int $count Number of GUIDs to generate
     */
    public function bulkCreateIds(int $count): array {
        if($count == 0) {
            return [];
        }

        $guids = [];

        for($i = 0; $i < $count; $i++) {
            $guids[] = GUID::generate();
        }

        return $guids;
    }
}

?>