<?php

namespace App\Core\Caching;

use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Exceptions\CacheException;
use Exception;

/**
 * Persistent cache that is loaded and held in memory until it's content is saved to disk
 * 
 * @author Lukas Velek
 */
class Cache {
    private array $data;
    private ?DateTime $expirationDate;
    private bool $invalidated;
    private string $hash;
    private string $namespace;
    private ?DateTime $lastWriteDate;
    private CacheFactory $cacheFactory;
    private CacheLogger $logger;
    private ?string $customNamespace;

    /**
     * Class constructor
     * 
     * @param array $data Loaded data
     * @param string $namespace Namespace
     * @param ?DateTime $expirationDate Cache expiration date
     * @param ?DateTime $lastWriteDate Date of last write
     */
    public function __construct(array $data, string $namespace, CacheFactory $cacheFactory, CacheLogger $logger, ?DateTime $expirationDate = null, ?DateTime $lastWriteDate = null) {
        $this->data = $data;
        $this->expirationDate = $expirationDate;
        $this->invalidated = false;
        $this->namespace = $namespace;
        $this->lastWriteDate = $lastWriteDate;
        $this->cacheFactory = $cacheFactory;
        $this->logger = $logger;

        $this->customNamespace = null;

        $this->hash = HashManager::createHash(256);
    }

    /**
     * Sets the custom namespace
     * 
     * @param ?string $customNamespace Custom namespace
     */
    public function setCustomNamespace(?string $customNamespace) {
        $this->customNamespace = $customNamespace;
    }

    /**
     * Loads data from cache
     * 
     * @param mixed $key Data key
     * @param callback $generator Data generator
     * @param array $generatorDependencies Data generator dependencies (arguments)
     * @param bool $force Force generate result or not
     * @return mixed|null Data or null
     */
    public function load(mixed $key, callable $generator, array $generatorDependencies = [], bool $force = false) {
        if(array_key_exists(($this->customNamespace ?? '') . $key, $this->data) && !$force) {
            $this->logger->logHitMiss(($this->customNamespace ?? '') . $key, $this->namespace, true, __METHOD__);
            return $this->data[($this->customNamespace ?? '') . $key];
        } else {
            try {
                $result = $generator(...$generatorDependencies);
            } catch(Exception $e) {
                throw new CacheException('Could not save data to cache. Reason: ' . $e->getMessage(), $this->namespace, $e);
            }

            $this->logger->logHitMiss(($this->customNamespace ?? '') . $key, $this->namespace, false, __METHOD__);

            $this->data[$key] = $result;
            $this->lastWriteDate = new DateTime();

            return $result;
        }
    }

    /**
     * Saves data to cache
     * 
     * @param mixed $key Data key
     * @param callback $generator Data generator
     * @param array $generatorDependencies Data generator dependencies (arguments)
     */
    public function save(mixed $key, callable $generator, array $generatorDependencies = []) {
        try {
            $result = $generator(...$generatorDependencies);
        } catch(Exception $e) {
            throw new CacheException('Could not save data to cache.', $this->namespace, $e);
        }

        $this->data[($this->customNamespace ?? '') . $key] = $result;

        $this->lastWriteDate = new DateTime();
    }

    /**
     * Updates data in cache
     * 
     * Method checks if given key exists in cache and is different. The data is saved to cache only if the does not exist or exists but is different.
     * 
     * @param mixed $key Data key
     * @param callback $generator Data generator
     * @param array $generatorDependencies Data generator dependencies (arguments)
     */
    public function update(mixed $key, callable $generator, array $generatorDependencies = []) {
        try {
            $result = $generator(...$generatorDependencies);
        } catch(Exception $e) {
            throw new CacheException('Could not save data to cache.', $this->namespace, $e);
        }

        $isSave = false;

        if(array_key_exists(($this->customNamespace ?? '') . $key, $this->data)) {
            if($this->data[($this->customNamespace ?? '') . $key] != $result) {
                // key exists and data is different
                $isSave = true;
            }
        } else {
            // key does not exist
            $isSave = true;
        }

        if($isSave) {
            $this->data[($this->customNamespace ?? '') . $key] = $result;
            $this->lastWriteDate = new DateTime();
        }
    }

    /**
     * Invalidates cache
     */
    public function invalidate() {
        $this->data = [];
        $this->invalidated = true;
        $this->logger->logCacheInvalidated($this->namespace, __METHOD__);
    }

    /**
     * Invalidates a single key in cache
     * 
     * @param mixed $key Cache key
     */
    public function invalidateKey(mixed $key) {
        if(array_key_exists(($this->customNamespace ?? '') . $key, $this->data)) {
            unset($this->data[($this->customNamespace ?? '') . $key]);
        }
    }

    /**
     * Returns hash
     * 
     * @return string Hash
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * Returns data
     * 
     * @return array Data
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Returns last write date
     * 
     * @return DateTime Last write date
     */
    public function getLastWriteDate() {
        return $this->lastWriteDate;
    }

    /**
     * Returns expiration date
     * 
     * @return DateTime Expiration date
     */
    public function getExpirationDate() {
        return $this->expirationDate;
    }

    /**
     * Is cache invalidated?
     * 
     * @return bool True if invalidated or false if not
     */
    public function isInvalidated() {
        return $this->invalidated;
    }

    /**
     * Returns namespace
     * 
     * @return string Namespace
     */
    public function getNamespace() {
        return $this->namespace;
    }

    /**
     * Is cache filled?
     * 
     * @return bool True if data is cache or false if the cache is empty
     */
    public function isCached() {
        return !empty($this->cache);
    }
}

?>