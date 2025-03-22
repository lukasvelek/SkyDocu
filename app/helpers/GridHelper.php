<?php

namespace App\Helpers;

use App\Core\Caching\Cache;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Logger\Logger;

/**
 * GridHelper contains useful function for working with grids
 * 
 * @author Lukas Velek
 */
class GridHelper {
    public const GRID_CONTAINERS = 'gridContainers';
    public const GRID_BACKGROUND_SERVICES = 'gridBackgroundServices';
    public const GRID_USERS = 'gridUsers';

    private Logger $logger;
    private array $gridPageData;
    private string $currentUserId;
    private CacheFactory $cacheFactory;
    private ?string $containerId;

    private Cache $gridPageDataCache;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     * @param string $currentUserId Current user ID
     * @param ?string $containerId Current container ID
     */
    public function __construct(Logger $logger, string $currentUserId, ?string $containerId = null) {
        $this->logger = $logger;
        $this->currentUserId = $currentUserId;
        $this->containerId = $containerId;

        $this->gridPageData = [];
    }

    /**
     * Sets CacheFactory instance
     * 
     * @param CacheFactory $cacheFactory CacheFactory instance
     */
    public function setCacheFactory(CacheFactory $cacheFactory) {
        $this->cacheFactory = $cacheFactory;

        if($this->containerId !== null) {
            $this->cacheFactory->setCustomNamespace($this->containerId);
        }

        $this->gridPageDataCache = $this->cacheFactory->getCache(CacheNames::GRID_PAGE_DATA);
    }

    /**
     * Returns grid page
     * 
     * @param string $gridName Grid name
     * @param int $gridPage Current grid page
     * @param array $customParams Custom parameters
     * @return int Grid page
     */
    public function getGridPage(string $gridName, int $gridPage, array $customParams = []) {
        $page = $this->loadGridPageData($gridName, $customParams);

        if($page != $gridPage && $gridPage > -1) {
            $page = $gridPage;

            $this->saveGridPageData($gridName, $page, $customParams);
        }

        return $page;
    }

    /**
     * Loads grid page data from cache
     * 
     * @param string $gridName Grid name
     * @param array $customParams Custom parameters
     * @return int Grid page
     */
    private function loadGridPageData(string $gridName, array $customParams) {
        $key = $this->createCacheKey($gridName, $customParams);

        if(!array_key_exists($key, $this->gridPageData)) {
            $page = $this->gridPageDataCache->load($key, function() { return 0; });

            $this->gridPageData[$key] = $page;
        }

        $this->logger->info(sprintf('Loaded page for grid \'%s\': %d.', $key, $this->gridPageData[$key]), __METHOD__);

        return $this->gridPageData[$key];
    }

    /**
     * Saves grid page data to cache
     * 
     * @param string $gridName Grid name
     * @param int $page Current grid page
     * @param array $customParams Custom parameters
     * @return void
     */
    private function saveGridPageData(string $gridName, int $page, array $customParams) {
        $key = $this->createCacheKey($gridName, $customParams);

        $this->gridPageData[$key] = $page;

        return $this->gridPageDataCache->save($key, function() use ($page) { return $page; });
    }

    /**
     * Creates cache key for current user, current grid and custom parameters
     * 
     * @param string $gridName Grid name
     * @param array $customParams Custom parameters
     * @return string Cache key
     */
    private function createCacheKey(string $gridName, array $customParams) {
        if(!empty($customParams)) {
            return $this->currentUserId . '_' . $gridName . '_' . implode('-', $customParams);
        } else {
            return $this->currentUserId . '_' . $gridName;
        }
    }
}

?>