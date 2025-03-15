<?php

namespace App\Managers;

use App\Constants\Container\SystemGroups;
use App\Constants\ContainerStatus;
use App\Core\Caching\CacheNames;
use App\Core\DatabaseConnection;
use App\Core\DB\DatabaseManager;
use App\Core\DB\DatabaseMigrationManager;
use App\Core\HashManager;
use App\Entities\ContainerEntity;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\Container\GroupRepository;
use App\Repositories\ContainerRepository;
use App\Repositories\ContentRepository;
use App\Repositories\UserRepository;

class ContainerManager extends AManager {
    public ContainerRepository $containerRepository;
    private DatabaseManager $dbManager;
    private GroupManager $groupManager;
    private DatabaseConnection $masterConn;
    private ContainerDatabaseManager $containerDatabaseManager;

    public function __construct(Logger $logger, EntityManager $entityManager, ContainerRepository $containerRepository, DatabaseManager $dbManager, GroupManager $groupManager, DatabaseConnection $masterConn, ContainerDatabaseManager $containerDatabaseManager) {
        parent::__construct($logger, $entityManager);

        $this->containerRepository = $containerRepository;
        $this->dbManager = $dbManager;
        $this->groupManager = $groupManager;
        $this->masterConn = $masterConn;
        $this->containerDatabaseManager = $containerDatabaseManager;
    }

    /**
     * Returns generated database name for given containerId
     * 
     * @param string $containerId
     */
    public function generateContainerDatabaseName(string $containerId): string {
        return 'sd_db_' . $containerId . '_' . HashManager::createHash(8, false);
    }

    /**
     * Creates a new container
     * 
     * @param string $title Container title
     * @param string $description Container description
     * @param string $callingUserId Calling user ID
     * @param int $environment Container environment
     * @param bool $canShowReferent Can show referent
     * @param int $status Container status
     */
    public function createNewContainer(string $title, string $description, string $callingUserId, int $environment, bool $canShowReferent, int $status = ContainerStatus::NEW) {
        $containerId = $this->createId(EntityManager::CONTAINERS);
        $databaseName = $this->generateContainerDatabaseName($containerId);

        $this->containerDatabaseManager->insertNewContainerDatabase($containerId, $databaseName, 'SkyDocu Database', 'Default SkyDocu database', true);

        $data = [
            'containerId' => $containerId,
            'userId' => $callingUserId,
            'title' => $title,
            'description' => $description,
            'environment' => $environment,
            'canShowContainerReferent' => ($canShowReferent ? 1 : 0),
            'status' => $status
        ];

        if(!$this->containerRepository->createNewContainer($data)) {
            throw new GeneralException('Could not create a new container.');
        }

        if($status != ContainerStatus::REQUESTED) {
            $statusId = $this->createId(EntityManager::CONTAINER_CREATION_STATUS);
            if(!$this->containerRepository->createNewCreationStatusEntry($statusId, $containerId)) {
                throw new GeneralException('Could not queue container for background creation.');
            }

            $this->groupManager->createNewGroup($title . ' - users', [$callingUserId], $containerId);

            if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS) ||
                !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS) ||
                !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::NAVBAR_CONTAINER_SWITCH_USER_MEMBERSHIPS)) {
                throw new GeneralException('Could not invalidate cache.');
            }
        }

        return $containerId;
    }

    /**
     * Creates a new container
     * 
     * This method is used asynchronously by a background service.
     * 
     * @param string $containerId Container ID
     */
    public function createNewContainerAsync(string $containerId) {
        $container = $this->getContainerById($containerId, true);

        try {
            $this->runContainerDatabaseMigrations($container->getDefaultDatabase()->getName(), $containerId);
        } catch(AException $e) {
            throw $e;
        }
    }

    /**
     * Runs container database migrations
     * 
     * @param string $dbName Container database name
     * @param string $containerId Container ID
     */
    private function runContainerDatabaseMigrations(string $dbName, string $containerId) {
        try {
            $conn = $this->dbManager->getConnectionToDatabase($dbName);
        } catch(AException $e) {
            throw new GeneralException('Could not establish connection to the container database. Reason: ' . $e->getMessage(), $e);
        }

        $migrationManager = new DatabaseMigrationManager($this->masterConn, $conn, $this->logger);
        $migrationManager->setContainer($containerId);

        $migrationManager->runMigrations();
    }

    public function checkContainerTitleExists(string $title) {
        return $this->containerRepository->checkTitleExists($title);
    }

    public function changeContainerStatus(string $containerId, int $newStatus, string $callingUserId, string $description) {
        $historyId = $this->createId(EntityManager::CONTAINER_STATUS_HISTORY);

        $container = $this->getContainerById($containerId);

        if(!$this->containerRepository->createNewStatusHistoryEntry($historyId, $containerId, $callingUserId, $description, $container->getStatus(), $newStatus)) {
            throw new GeneralException('Could not create new status history change entry.');
        }

        if(!$this->containerRepository->updateContainer($containerId, [
            'status' => $newStatus
        ])) {
            throw new GeneralException('Could not change status.');
        }

        $result = $this->cacheFactory->invalidateAllCache();

        if(!$result) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function changeContainerCreationStatus(string $containerId, int $percent, ?string $description) {
        $data = [
            'percentFinished' => (string)$percent,
            'description' => $description
        ];

        if(!$this->containerRepository->updateCreationStatusEntry($containerId, $data)) {
            throw new GeneralException('Could not update container creation status.');
        }
    }

    public function getContainerById(string $containerId, bool $force = false): ContainerEntity {
        $cache = $this->cacheFactory->getCache(CacheNames::CONTAINERS);

        $container = $cache->load($containerId, function() use ($containerId) {
            return $this->containerRepository->getContainerById($containerId);
        }, [], $force);

        if($container === null) {
            throw new NonExistingEntityException('Entity does not exist.');
        }
        
        $containerEntity = ContainerEntity::createEntityFromDbRow($container);

        $dbCache = $this->cacheFactory->getCache(CacheNames::CONTAINER_DATABASES);

        $databases = $dbCache->load($containerId, function() use ($containerId) {
            return $this->containerDatabaseManager->getContainerDatabasesForContainerId($containerId);
        });

        $containerEntity->addContainerDatabases($databases);

        return $containerEntity;
    }

    public function deleteContainer(string $containerId, bool $isRequest = false) {
        $container = $this->getContainerById($containerId);
        
        if(!$isRequest) {
            // Drop container database with all data
            try {
                foreach($container->getDatabases() as $database) {
                    $this->dbManager->dropDatabase($database->getName());
                }
            } catch(AException $e) {}

            // Remove group and memberships
            $group = null;
            try {
                $group = $this->groupManager->getGroupByTitle($container->getTitle() . ' - users');
            } catch(AException $e) {}

            if($group !== null) {
                $exceptions = [];
                $this->groupManager->removeAllUsersFromGroup($group->groupId, $exceptions);
    
                $this->groupManager->removeGroup($group->groupId);
            }
        }
        
        // Delete container
        if(!$this->containerRepository->deleteContainer($containerId)) {
            throw new GeneralException('Could not delete container.');
        }

        if(!$isRequest) {
            // Delete container history data
            if(!$this->containerRepository->deleteContainerCreationStatus($containerId)) {
                throw new GeneralException('Could not delete container creation status entry.');
            }
            if(!$this->containerRepository->deleteContainerStatusHistory($containerId)) {
                throw new GeneralException('COuld not delete container status history entries.');
            }
        }

        // Drop container custom databases
        $databases = $this->containerDatabaseManager->getContainerDatabasesForContainerId($containerId);

        foreach($databases as $database) {
            $this->containerDatabaseManager->dropDatabaseByEntryId($containerId, $database->getId());
        }
    }

    public function addUserToContainer(string $userId, string $containerId) {
        $container = $this->getContainerById($containerId);

        $conn = $this->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

        $userRepository = new UserRepository($this->containerRepository->conn, $this->logger);
        $groupRepository = new GroupRepository($conn, $this->logger);
        $contentRepository = new ContentRepository($conn, $this->logger);
        $entityManager = new EntityManager($this->logger, $contentRepository);

        $groupManager = new Container\GroupManager($this->logger, $entityManager, $groupRepository, $userRepository);

        $groupManager->addUserToGroupTitle(SystemGroups::ALL_USERS, $userId);
    }

    public function updateContainer(string $containerId, array $data) {
        if(!$this->containerRepository->updateContainer($containerId, $data)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::CONTAINERS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function getContainerUsageStatisticsTotalCount(string $containerId) {
        $qb = $this->containerRepository->composeQueryForContainerUsageStatistics($containerId);

        $qb->select(['COUNT(entryId) AS count'])
            ->execute();

        return $qb->fetch('count');
    }

    public function deleteContainerUsageStatistics(string $containerId, int $limit, bool $deleteAll) {
        $entries = [];

        if(!$deleteAll) {
            $qb = $this->containerRepository->composeQueryForContainerUsageStatistics($containerId);
            $qb->limit($limit)
                ->orderBy('date', 'DESC')
                ->select(['entryId'])
                ->execute();

            while($row = $qb->fetchAssoc()) {
                $entries[] = $row['entryId'];
            }
        }

        if(!$this->containerRepository->deleteContainerUsageStatistics($containerId, $entries)) {
            throw new GeneralException('Database error.');
        }
    }

    public function declineContainerRequest(string $containerId) {
        $this->deleteContainer($containerId, true);
    }

    public function approveContainerRequest(string $containerId, string $callingUserId) {
        $container = $this->getContainerById($containerId);

        $this->updateContainer($containerId, [
            'status' => ContainerStatus::NEW
        ]);

        $statusId = $this->createId(EntityManager::CONTAINER_CREATION_STATUS);
        if(!$this->containerRepository->createNewCreationStatusEntry($statusId, $containerId)) {
            throw new GeneralException('Could not queue container for background creation.');
        }

        $this->groupManager->createNewGroup($container->getTitle() . ' - users', [$callingUserId], $containerId);

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::NAVBAR_CONTAINER_SWITCH_USER_MEMBERSHIPS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }
}

?>