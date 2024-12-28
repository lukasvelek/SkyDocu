<?php

namespace App\Core\Caching;

use App\Core\Caching\Cache;
use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use App\Core\HashManager;

/**
 * CacheFactory allows to create cache
 * 
 * @author Lukas Velek
 */
class CacheFactory {
    private const I_NS_DATA = '_data';
    private const I_NS_CACHE_EXPIRATION = '_cacheExpirationDate';
    private const I_NS_CACHE_LAST_WRITE_DATE = '_cacheLastWriteDate';

    private CacheLogger $cacheLogger;

    /** @var array<Cache> */
    private array $persistentCaches;

    private ?string $customNamespace;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     */
    public function __construct() {
        $this->persistentCaches = [];

        $this->cacheLogger = new CacheLogger();

        $this->customNamespace = null;
    }

    /**
     * Class destructor
     */
    public function __destruct() {
        $this->saveCaches();

        $this->persistentCaches = [];
    }

    /**
     * Sets custom additional namespace
     * 
     * @param string $namespace Custom additional namespace
     */
    public function setCustomNamespace(string $namespace) {
        $this->customNamespace = $namespace;
    }

    /**
     * Invalidates cache by Cache instance
     * 
     * @param Cache $cache Cache instance
     * @return bool True on success or false on failure
     */
    public function invalidateCacheByCache(Cache $cache) {
        return $this->invalidateCacheByNamespace($cache->getNamespace());
    }

    /**
     * Invalidates cache by namespace
     * 
     * @param string $namespace Namespace
     * @return bool True on success or false on failure
     */
    public function invalidateCacheByNamespace(string $namespace) {
        $expire = new DateTime(time() - 60);
        $cache = $this->getCache($namespace, $expire);
        $cache->invalidate();

        $this->saveCaches();

        return true;
    }

    /**
     * Invalidates all cache namespaces
     * 
     * @return bool True on success or false on failure
     */
    public function invalidateAllCache() {
        $this->persistentCaches = [];

        $namespaces = CacheNames::getAll();

        foreach($namespaces as $namespace) {
            FileManager::deleteFolderRecursively(APP_ABSOLUTE_DIR . CACHE_DIR . $namespace . '\\', false);
        }

        return true;
    }

    /**
     * Returns a new instance of persistance cache
     * 
     * If persistent cache with given namespace already exists, the data is loaded and passed to the instance.
     * 
     * @param string $namespace Namespace
     * @param DateTime $expiration Expiration date
     * @return Cache Persistent cache
     */
    public function getCache(string $namespace, ?DateTime $expiration = null) {
        $cacheData = $this->loadDataFromCache($namespace);

        if($cacheData === null) {
            $this->cacheLogger->logCacheCreateOrGet($namespace, true, __METHOD__);
            $cache = new Cache([], $namespace, $this, $this->cacheLogger, $expiration, null);
            $this->persistentCaches[$cache->getHash()] = &$cache;
            return $cache;
        }

        $expirationDate = null;
        if(isset($cacheData[self::I_NS_CACHE_EXPIRATION])) {
            $expirationDate = new DateTime(strtotime($cacheData[self::I_NS_CACHE_EXPIRATION]));
        }

        if($expiration !== null) {
            $expirationDate = $expiration;
        }

        if($expirationDate !== null && strtotime($expirationDate) < time()) {
            // cache has expired
            $cacheData = null;
            $expiration = null;
            $expirationDate = null;

            $this->cacheLogger->logCacheCreateOrGet($namespace, true, __METHOD__);
            $cache = new Cache([], $namespace, $this, $this->cacheLogger, $expiration, null);
            $this->persistentCaches[$cache->getHash()] = &$cache;
            return $cache;
        }

        $lastWriteDate = null;
        if(isset($cacheData[self::I_NS_CACHE_LAST_WRITE_DATE])) {
            $lastWriteDate = new DateTime(strtotime($cacheData[self::I_NS_CACHE_LAST_WRITE_DATE]));
        }

        $this->cacheLogger->logCacheCreateOrGet($namespace, false, __METHOD__);

        $cache = new Cache($cacheData[self::I_NS_DATA], $namespace, $this, $this->cacheLogger, $expirationDate, $lastWriteDate);
        $this->persistentCaches[$cache->getHash()] = &$cache;
        return $cache;
    }

    /**
     * Loads data from persistent cache
     * 
     * @param string $namespace Namespace
     * @return mixed|null Loaded content or null
     */
    private function loadDataFromCache(string $namespace) {
        $path = APP_ABSOLUTE_DIR . CACHE_DIR . $namespace . '\\';

        if($this->customNamespace !== null) {
            $path .= $this->customNamespace . '\\';
        }
        
        $date = new DateTime();
        $date->format('Y-m-d');

        $filename = $date . $namespace;
        $filename = md5($filename);

        $content = $this->loadFileContent($path, $filename);

        if($content === null) {
            return $content;
        }

        $content = unserialize($content);

        return $content;
    }

    /**
     * Loads file content
     * 
     * @param string $path Path
     * @param string $filename Filename
     * @return string|null File content or null if file does not exist
     */
    private function loadFileContent(string $path, string $filename) {
        if(!FileManager::fileExists($path . $filename)) {
            return null;
        }

        $content = FileManager::loadFile($path . $filename);

        if($content === false) {
            return null;
        }

        return $content;
    }

    /**
     * Saves persistent caches
     */
    public function saveCaches() {
        foreach($this->persistentCaches as $cache) {
            if($cache->isInvalidated()) {
                $this->deleteCache($cache->getNamespace());
            } else {
                $_cache = $this->getCache($cache->getNamespace());
                $_data = $_cache->getData();

                if($_data != $cache->getData()) {
                    $tmp = [
                        self::I_NS_DATA => $cache->getData(),
                        self::I_NS_CACHE_EXPIRATION => $cache->getExpirationDate()?->getResult(),
                        self::I_NS_CACHE_LAST_WRITE_DATE => $cache->getLastWriteDate()?->getResult()
                    ];
    
                    $this->saveDataToCache($cache->getNamespace(), $tmp);
                }
            }
        }
    }

    /**
     * Save persistent cache to disk
     * 
     * @param string $namespace Namespace
     * @param array $data Persistent cache data
     * @return bool True on success or false on failure
     */
    private function saveDataToCache(string $namespace, array $data) {
        $path = APP_ABSOLUTE_DIR . CACHE_DIR . $namespace . '\\';

        if($this->customNamespace !== null) {
            $path .= $this->customNamespace . '\\';
        }
        
        $date = new DateTime();
        $date->format('Y-m-d');

        $filename = $date . $namespace;
        $filename = md5($filename);

        $result = FileManager::saveFile($path, $filename, serialize($data), true, false);

        return $result !== false;
    }

    /**
     * Deletes persistent cache
     * 
     * @param string $namespace Namespace
     * @return bool True on success or false on failure
     */
    private function deleteCache(string $namespace) {
        $path = APP_ABSOLUTE_DIR . CACHE_DIR . $namespace . '\\';

        if($this->customNamespace !== null) {
            $path .= $this->customNamespace . '\\';
        }
        
        $result = FileManager::deleteFolderRecursively($path, false);
        
        if($result === true) {
            $this->cacheLogger->logCacheNamespaceDeleted($namespace, __METHOD__);
        }

        return $result;
    }
}

?>