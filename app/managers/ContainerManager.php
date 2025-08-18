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

/**
 * ContainerManager is used for managing containers
 * 
 * @author Lukas Velek
 */
class ContainerManager extends AManager {
    public ContainerRepository $containerRepository;
    private DatabaseManager $dbManager;
    private GroupManager $groupManager;
    private DatabaseConnection $masterConn;
    private ContainerDatabaseManager $containerDatabaseManager;

    public function __construct(Logger $logger, ContainerRepository $containerRepository, DatabaseManager $dbManager, GroupManager $groupManager, DatabaseConnection $masterConn, ContainerDatabaseManager $containerDatabaseManager) {
        parent::__construct($logger);

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
        $prefix = CONTAINER_DB_NAME_PREFIX;
        if(!str_ends_with($prefix, '_') && $prefix != '') {
            $prefix .= '_';
        }
        return $prefix . 'sd_db_' . $containerId . '_' . HashManager::createHash(8, false);
    }

    /**
     * Creates a new container
     * 
     * @param string $title Container title
     * @param string $description Container description
     * @param string $callingUserId Calling user ID
     * @param bool $canShowReferent Can show referent
     * @param int $status Container status
     */
    public function createNewContainer(string $title, string $description, string $callingUserId, bool $canShowReferent, int $status = ContainerStatus::NEW) {
        $containerId = $this->createId();
        $databaseName = $this->generateContainerDatabaseName($containerId);

        $this->containerDatabaseManager->insertNewContainerDatabase($containerId, $databaseName, 'SkyDocu Database', 'Default SkyDocu database', true);

        $data = [
            'containerId' => $containerId,
            'userId' => $callingUserId,
            'title' => $title,
            'description' => $description,
            'canShowContainerReferent' => ($canShowReferent ? 1 : 0),
            'status' => $status
        ];

        if(!$this->containerRepository->createNewContainer($data)) {
            throw new GeneralException('Could not create a new container.');
        }

        if($status != ContainerStatus::REQUESTED) {
            $statusId = $this->createId();
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
    public function runContainerDatabaseMigrations(string $dbName, string $containerId) {
        try {
            $conn = $this->dbManager->getConnectionToDatabase($dbName);
        } catch(AException $e) {
            throw new GeneralException('Could not establish connection to the container database. Reason: ' . $e->getMessage(), $e);
        }

        $migrationManager = new DatabaseMigrationManager($this->masterConn, $conn, $this->logger);
        $migrationManager->setContainer($containerId);

        $dbSchema = $migrationManager->runMigrations(true);

        if($this->containerDatabaseManager->updateContainerDatabase($containerId, $dbName, ['dbSchema' => $dbSchema])) {
            throw new GeneralException('Could not update database schema after migrations.');
        }
    }

    /**
     * Checks if container's title exists
     * 
     * @param string $title Title
     */
    public function checkContainerTitleExists(string $title): bool {
        return $this->containerRepository->checkTitleExists($title);
    }

    /**
     * Changes container's status
     * 
     * @param string $containerId Container ID
     * @param int $newStatus New status
     * @param string $callingUserId Calling user ID
     * @param string $description Description
     */
    public function changeContainerStatus(string $containerId, int $newStatus, string $callingUserId, string $description) {
        $historyId = $this->createId();

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

    /**
     * Changes container's creation status
     * 
     * @param string $containerId Container ID
     * @param int $percent Percent
     * @param ?string $description Description
     */
    public function changeContainerCreationStatus(string $containerId, int $percent, ?string $description) {
        $data = [
            'percentFinished' => (string)$percent,
            'description' => $description
        ];

        if(!$this->containerRepository->updateCreationStatusEntry($containerId, $data)) {
            throw new GeneralException('Could not update container creation status.');
        }
    }

    /**
     * Returns an instance of ContainerEntity for given container
     * 
     * @param string $containerId Container ID
     * @param bool $force Force fetch data from the database (overrides cache and updates it)
     */
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

    /**
     * Deletes container and all its data
     * 
     * @param string $containerId Container ID
     * @param bool $isRequest True if it is a request or false if it is not
     */
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

        $this->cacheFactory->invalidateAllCache();
    }

    /**
     * Adds user to given container
     * 
     * @param string $userId User ID
     * @param string $containerId Container ID
     */
    public function addUserToContainer(string $userId, string $containerId) {
        $container = $this->getContainerById($containerId);

        $conn = $this->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

        $userRepository = new UserRepository($this->containerRepository->conn, $this->logger, $this->containerRepository->transactionLogRepository);
        $groupRepository = new GroupRepository($conn, $this->logger, $this->containerRepository->transactionLogRepository);
        $contentRepository = new ContentRepository($conn, $this->logger, $this->containerRepository->transactionLogRepository);

        $groupManager = new Container\GroupManager($this->logger, $groupRepository, $userRepository);

        $groupManager->addUserToGroupTitle(SystemGroups::ALL_USERS, $userId);
    }

    /**
     * Updates container
     * 
     * @param string $containerId Container ID
     * @param array $data New data
     */
    public function updateContainer(string $containerId, array $data) {
        if(!$this->containerRepository->updateContainer($containerId, $data)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::CONTAINERS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    /**
     * Updates containers in bulk
     * 
     * @param array $containerIds Container IDs
     * @param array $data Data array
     * @throws GeneralException
     */
    public function bulkUpdateContainers(array $containerIds, array $data) {
        if(!$this->containerRepository->bulkUpdateContainers($containerIds, $data)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::CONTAINERS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    /**
     * Returns the number of all usage statistics entries for given container
     * 
     * @param string $containerId Container ID
     */
    public function getContainerUsageStatisticsTotalCount(string $containerId): ?int {
        $qb = $this->containerRepository->composeQueryForContainerUsageStatistics($containerId);

        $qb->select(['COUNT(entryId) AS count'])
            ->execute();

        return $qb->fetch('count');
    }

    /**
     * Deletes container usage statistics
     * 
     * @param string $containerId Container ID
     * @param int $limit Number of entries to delete
     * @param bool $deleteAll True if all usage statistics for given container should be deleted
     */
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

    /**
     * Declines container request
     * 
     * @param string $containerId Container ID
     */
    public function declineContainerRequest(string $containerId) {
        $this->deleteContainer($containerId, true);
    }

    /**
     * Approves container request and adds it to the container creation queue
     * 
     * @param string $container Container ID
     * @param string $callingUserId Calling user ID
     */
    public function approveContainerRequest(string $containerId, string $callingUserId) {
        $container = $this->getContainerById($containerId);

        $this->updateContainer($containerId, [
            'status' => ContainerStatus::NEW
        ]);

        $statusId = $this->createId();
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

    /**
     * Returns all containers that are in distribution
     * 
     * @return array<int, \App\Entities\ContainerEntity>
     */
    public function getContainersInDistribution(): array {
        $qb = $this->containerRepository->composeQueryForContainers();
        $qb->andWhere('isInDistribution = ?', ['1'])
            ->andWhere($qb->getColumnInValues('status', [ContainerStatus::NOT_RUNNING, ContainerStatus::RUNNING]))
            ->execute();

        $containerIds = [];
        while($row = $qb->fetchAssoc()) {
            $containerIds[] = $row['containerId'];
        }

        $containers = [];
        foreach($containerIds as $containerId) {
            $containers[] = $this->getContainerById($containerId, true);
        }

        return $containers;
    }

    /**
     * Returns instances of all containers
     * 
     * @param bool $returnEntities If true then an array of entities is returned or if false an array of IDs is returned
     */
    public function getAllContainers(bool $returnEntities = true, bool $activeOnly = false): array {
        $qb = $this->containerRepository->composeQueryForContainers();

        if($activeOnly) {
            $qb->andWhere($qb->getColumnInValues('status', [ContainerStatus::NOT_RUNNING, ContainerStatus::RUNNING]));
        }

        $qb->execute();

        $containerIds = [];
        while($row = $qb->fetchAssoc()) {
            $containerIds[] = $row['containerId'];
        }

        if($returnEntities) {
            $containers = [];
            foreach($containerIds as $containerId) {
                $containers[] = $this->getContainerById($containerId, true);
            }

            return $containers;
        } else {
            return $containerIds;
        }
    }
}

?>