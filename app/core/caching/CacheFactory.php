<?php

namespace App\Core\Caching;

use App\Core\Caching\Cache;
use App\Core\Datetypes\DateTime;
use App\Core\FileManager;

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
        $messages = [];
        $this->saveCaches($messages);

        $this->persistentCaches = [];
    }

    /**
     * Class clone
     */
    public function __clone() {
        $obj = new self();

        $obj->persistentCaches = &$this->persistentCaches;

        return $obj;
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
        $messages1 = [];
        return $this->deleteCache($namespace, $messages1);
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
            //FileManager::deleteFolderRecursively(APP_ABSOLUTE_DIR . CACHE_DIR . $namespace . '\\', false);
            $this->invalidateCacheByNamespace($namespace);
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
            $cache->setCustomNamespace($this->customNamespace);
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
            $cache->setCustomNamespace($this->customNamespace);
            $this->persistentCaches[$cache->getHash()] = &$cache;
            return $cache;
        }

        $lastWriteDate = null;
        if(isset($cacheData[self::I_NS_CACHE_LAST_WRITE_DATE])) {
            $lastWriteDate = new DateTime(strtotime($cacheData[self::I_NS_CACHE_LAST_WRITE_DATE]));
        }

        $this->cacheLogger->logCacheCreateOrGet($namespace, false, __METHOD__);

        $cache = new Cache($cacheData[self::I_NS_DATA], $namespace, $this, $this->cacheLogger, $expirationDate, $lastWriteDate);
        $cache->setCustomNamespace($this->customNamespace);
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

        /*if($this->customNamespace !== null) {
            $path .= $this->customNamespace . '\\';
        }*/
        
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
     * 
     * @param array &$messages Messages
     * @return bool True on success or false on failure
     */
    public function saveCaches(array &$messages) {
        foreach($this->persistentCaches as $cache) {
            if($cache->isInvalidated()) {
                return $this->deleteCache($cache->getNamespace(), $messages);
            } else {
                $_cache = $this->getCache($cache->getNamespace());
                $_data = $_cache->getData();

                if($_data != $cache->getData()) {
                    $tmp = [
                        self::I_NS_DATA => $cache->getData(),
                        self::I_NS_CACHE_EXPIRATION => $cache->getExpirationDate()?->getResult(),
                        self::I_NS_CACHE_LAST_WRITE_DATE => $cache->getLastWriteDate()?->getResult()
                    ];
    
                    return $this->saveDataToCache($cache->getNamespace(), $tmp, $messages);
                }
            }
        }
    }

    /**
     * Save persistent cache to disk
     * 
     * @param string $namespace Namespace
     * @param array $data Persistent cache data
     * @param array &$messages Messages
     * @return bool True on success or false on failure
     */
    private function saveDataToCache(string $namespace, array $data, array &$messages) {
        $path = APP_ABSOLUTE_DIR . CACHE_DIR . $namespace . '\\';

        /*if($this->customNamespace !== null) {
            $path .= $this->customNamespace . '\\';
        }*/
        
        $date = new DateTime();
        $date->format('Y-m-d');

        $filename = $date . $namespace;
        $filename = md5($filename);

        if(FileManager::fileExists($path . $filename)) {
            $messages[] = 'File \'' . $path . $filename . '\' already exists.';
        }

        $result = FileManager::saveFile($path, $filename, serialize($data), true, false);

        $messages[] = 'Result after saving file: ' . var_export($result, true);

        return $result !== false;
    }

    /**
     * Deletes persistent cache
     * 
     * @param string $namespace Namespace
     * @param array &$messages Messages
     * @return bool True on success or false on failure
     */
    private function deleteCache(string $namespace, array &$messages) {
        // An instance of the cache with given namespace might exist and contain data and so it must be destroyed
        $tmp = null;
        foreach($this->persistentCaches as $hash => $cache) {
            if($cache->getNamespace() == $namespace) {
                $tmp = $hash;
            }
        }

        if($tmp !== null) {
            unset($this->persistentCaches[$tmp]);
        }

        $path = APP_ABSOLUTE_DIR . CACHE_DIR . $namespace . '\\';

        /*if($this->customNamespace !== null) {
            $path .= $this->customNamespace . '\\';
        }*/
        
        $messages[] = 'Deleting \'' . $path . '\'. ';

        if(!FileManager::folderExists($path)) {
            $messages[] = 'Cache namespace \'' . $namespace . '\' does not exist in \'' . $path . '\'.';
            return true;
        }

        $result = FileManager::deleteFolderRecursively($path, true);

        $messages[] = 'Result after deleting folder (resursively): ' . $result;
        
        if($result === true) {
            $this->cacheLogger->logCacheNamespaceDeleted($namespace, __METHOD__);
        }

        return $result;
    }
}

?>