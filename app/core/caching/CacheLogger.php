<?php

namespace App\Core\Caching;

use App\Logger\Logger;

/**
 * CacheLogger is used for logging cache
 * 
 * @author Lukas Velek
 */
class CacheLogger extends Logger {
    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Logs cache hit or miss
     * 
     * @param string $key Seeked key
     * @param string $namespace Cache namespace
     * @param bool $hit True if key was found or false if not
     * @param string $method Calling method
     * @return bool True on success or false on failure
     */
    public function logHitMiss(string $key, string $namespace, bool $hit, string $method) {
        $text = 'Key \'' . $key . '\' ' . ($hit ? 'found' : 'not found') . ' in namespace \'' . $namespace . '\'.';

        return $this->internalLog($method, $text);
    }

    /**
     * Logs if cache namespace exists or had to be created
     * 
     * @param string $namespace Cache namespace
     * @param bool $created True if cache namespace had to be created or false if it existed
     * @param string $method Calling method
     * @return bool True on success or false on failure
     */
    public function logCacheCreateOrGet(string $namespace, bool $created, string $method) {
        $text = 'Cache namespace \'' . $namespace . '\' ' . ($created ? 'created' : 'found') . '.';
        
        return $this->internalLog($method, $text);
    }
    
    /**
     * Logs information that cache namespace has been invalidated
     * 
     * @param string $namespace Cache namespace
     * @param string $method Calling method
     * @return bool True on success or false on failure
     */
    public function logCacheInvalidated(string $namespace, string $method) {
        $text = 'Cache namespace \'' . $namespace . '\' invalidated.';

        return $this->internalLog($method, $text);
    }

    /**
     * Logs information that cache namespace has been deleted
     * 
     * @param string $namespace Cache namespace
     * @param string $method Calling method
     * @return bool True on success or false on failure
     */
    public function logCacheNamespaceDeleted(string $namespace, string $method) {
        $text = 'Cache namespace \'' . $namespace . '\' deleted.';

        return $this->internalLog($method, $text);
    }

    /**
     * Logs given information
     * 
     * @param string $method Calling method name
     * @param string $text Text
     * @return bool True on success or false on failure
     */
    private function internalLog(string $method, string $text) {
        return $this->log($method, $text, parent::LOG_CACHE);
    }
}

?>