<?php

namespace App\Managers;

use App\Constants\Container\StandaloneProcesses;
use App\Constants\Container\SystemGroups;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseManager;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Helpers\ContainerCreationHelper;
use App\Logger\Logger;
use App\Repositories\Container\GroupRepository;
use App\Repositories\ContainerRepository;
use App\Repositories\ContentRepository;
use App\Repositories\UserRepository;

class ContainerManager extends AManager {
    public ContainerRepository $containerRepository;
    private DatabaseManager $dbManager;
    private GroupManager $groupManager;

    public function __construct(Logger $logger, EntityManager $entityManager, ContainerRepository $containerRepository, DatabaseManager $dbManager, GroupManager $groupManager) {
        parent::__construct($logger, $entityManager);

        $this->containerRepository = $containerRepository;
        $this->dbManager = $dbManager;
        $this->groupManager = $groupManager;
    }

    public function createNewContainer(string $title, string $description, string $callingUserId, int $environment, bool $canShowReferent) {
        $containerId = $this->createId(EntityManager::CONTAINERS);
        $databaseName = 'sd_db_' . $containerId;

        $data = [
            'containerId' => $containerId,
            'userId' => $callingUserId,
            'title' => $title,
            'description' => $description,
            'databaseName' => $databaseName,
            'environment' => $environment,
            'canShowContainerReferent' => ($canShowReferent ? 1 : 0)
        ];

        if(!$this->containerRepository->createNewContainer($data)) {
            throw new GeneralException('Could not create a new container.');
        }

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

        return $containerId;
    }

    public function createNewContainerAsync(string $containerId) {
        $container = $this->containerRepository->getContainerById($containerId);

        if($container === null || $container === false) {
            throw new GeneralException('Container does not exist.');
        }

        $container = DatabaseRow::createFromDbRow($container);

        try {
            $this->dbManager->createNewDatabase($container->databaseName);

            $this->createNewContainerTables($container->databaseName);
            $this->createContainerTablesIndexes($container->databaseName);
            
            $exceptions = [];
            $this->insertNewContainerDefaultDataAsync($containerId, $container, $container->databaseName, $exceptions);
        } catch(AException $e) {
            throw $e;
        }
    }

    private function insertNewContainerDefaultDataAsync(string $containerId, DatabaseRow $container, string $dbName, array &$exceptions) {
        try {
            $conn = $this->dbManager->getConnectionToDatabase($dbName);
        } catch(AException $e) {
            throw new GeneralException('Could not establish connection to the container database.');
        }

        $users = $this->groupManager->getGroupUsersForGroupTitle($container->title . ' - users');

        $groupIds = [];
        foreach(SystemGroups::getAll() as $value => $text) {
            $groupIds[$value] = $this->createIdCustomDb(EntityManager::C_GROUPS, $conn);
        }

        $folderIds = [
            'Default' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_FOLDERS, $conn)
        ];

        $classIds = [
            'Default' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASSES, $conn)
        ];
        
        $data = [
            [
                'table' => 'document_classes',
                'data' => [
                    'classId' => $classIds['Default'],
                    'title' => 'Default'
                ]
            ],
            [
                'table' => 'document_class_group_rights',
                'data' => [
                    'rightId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASS_GROUP_RIGHTS, $conn),
                    'groupId' => $groupIds[SystemGroups::ALL_USERS],
                    'classId' => $classIds['Default'],
                    'canView' => 1,
                    'canCreate' => 1
                ]
            ],
            [
                'table' => 'document_class_group_rights',
                'data' => [
                    'rightId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASS_GROUP_RIGHTS, $conn),
                    'groupId' => $groupIds[SystemGroups::ADMINISTRATORS],
                    'classId' => $classIds['Default'],
                    'canView' => 1,
                    'canCreate' => 1,
                    'canEdit' => 1,
                    'canDelete' => 1
                ]
            ],
            [
                'table' => 'document_folders',
                'data' => [
                    'folderId' => $folderIds['Default'],
                    'title' => 'Default',
                    'isSystem' => 1
                ]
            ],
            [
                'table' => 'document_folder_group_relation',
                'data' => [
                    'relationId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_FOLDER_GROUP_RELATION, $conn),
                    'folderId' => $folderIds['Default'],
                    'groupId' => $groupIds[SystemGroups::ALL_USERS],
                    'canView' => 1,
                    'canCreate' => 1
                ]
            ],
            [
                'table' => 'document_folder_group_relation',
                'data' => [
                    'relationId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_FOLDER_GROUP_RELATION, $conn),
                    'folderId' => $folderIds['Default'],
                    'groupId' => $groupIds[SystemGroups::ADMINISTRATORS],
                    'canView' => 1,
                    'canCreate' => 1,
                    'canEdit' => 1,
                    'canDelete' => 1
                ]
            ],
            [
                'table' => 'group_rights_standard_operations',
                'data' => [
                    'rightId' => $this->createIdCustomDb(EntityManager::C_GROUP_STANDARD_OPERATION_RIGHTS, $conn),
                    'groupId' => $groupIds[SystemGroups::ADMINISTRATORS],
                    'canShareDocuments' => 1,
                    'canExportDocuments' => 1,
                    'canViewDocumentHistory' => 1
                ]
            ]
        ];

        foreach(StandaloneProcesses::getAll() as $key => $title) {
            $data[] = [
                'table' => 'process_types',
                'data' => [
                    'typeId' => $this->createIdCustomDb(EntityManager::C_PROCESS_TYPES, $conn),
                    'typeKey' => $key,
                    'title' => $title,
                    'description' => StandaloneProcesses::getDescription($key)
                ]
            ];
        }

        foreach($groupIds as $value => $groupId) {
            $data[] = [
                'table' => 'groups',
                'data' => [
                    'groupId' => $groupId,
                    'title' => $value
                ]
            ];
        }

        foreach($users as $userId) {
            foreach($groupIds as $name => $groupId) {
                $data[] = [
                    'table' => 'group_users_relation',
                    'data' => [
                        'relationId' => $this->createIdCustomDb(EntityManager::C_GROUP_USERS_RELATION, $conn),
                        'userId' => $userId,
                        'groupId' => $groupId
                    ]
                ];
            }
        }

        // DATA INSERT
        foreach($data as $part) {
            try {
                $tableName = $part['table'];
                $values = $part['data'];

                $this->dbManager->insertDataToTable($tableName, $values, $dbName);
            } catch(AException $e) {
                $exceptions[$tableName] = $e;
                continue;
            }
        }
    }

    private function createNewContainerTables(string $dbName) {
        $tables = ContainerCreationHelper::getContainerTableDefinitions();

        foreach($tables as $name => $definition) {
            if(!$this->dbManager->createTable($name, $definition, $dbName)) {
                throw new GeneralException('Could not create database table.');
            }
        }
    }

    private function createContainerTablesIndexes(string $dbName) {
        $indexes = ContainerCreationHelper::getContainerTableIndexDefinitions();

        $count = 1;
        foreach($indexes as $tableName => $columns) {
            try {
                if(!$this->dbManager->createTableIndex($dbName, $count, $tableName, $columns)) {
                    throw new GeneralException('Could not create database table indexes.');
                }
            } catch(AException $e) {
                $this->logger->exception($e, __METHOD__);
                continue;
            }

            $count++;
        }
    }

    public function checkContainerTitleExists(string $title) {
        return $this->containerRepository->checkTitleExists($title);
    }

    public function changeContainerStatus(string $containerId, int $newStatus, string $callingUserId, string $description) {
        $historyId = $this->createId(EntityManager::CONTAINER_STATUS_HISTORY);

        $container = $this->getContainerById($containerId);

        if(!$this->containerRepository->createNewStatusHistoryEntry($historyId, $containerId, $callingUserId, $description, $container->status, $newStatus)) {
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

    public function getContainerById(string $containerId, bool $force = false) {
        $cache = $this->cacheFactory->getCache(CacheNames::CONTAINERS);

        $container = $cache->load($containerId, function() use ($containerId) {
            return $this->containerRepository->getContainerById($containerId);
        }, [], $force);

        if($container === null) {
            throw new NonExistingEntityException('Entity does not exist.');
        }

        $container = $this->containerRepository->getContainerById($containerId);
        
        return DatabaseRow::createFromDbRow($container);
    }

    public function deleteContainer(string $containerId) {
        $container = $this->getContainerById($containerId);
        
        // Drop container database with all data
        $this->dbManager->dropDatabase($container->databaseName);

        // Remove group and memberships
        $group = $this->groupManager->getGroupByTitle($container->title . ' - users');

        $exceptions = [];
        $this->groupManager->removeAllUsersFromGroup($group->groupId, $exceptions);

        $this->groupManager->removeGroup($group->groupId);

        // Delete container and all history data
        if(!$this->containerRepository->deleteContainer($containerId)) {
            throw new GeneralException('Could not delete container.');
        }
        if(!$this->containerRepository->deleteContainerCreationStatus($containerId)) {
            throw new GeneralException('Could not delete container creation status entry.');
        }
        if(!$this->containerRepository->deleteContainerStatusHistory($containerId)) {
            throw new GeneralException('COuld not delete container status history entries.');
        }

        // Invalidate container cache
        $result = $this->cacheFactory->invalidateAllCache();

        if(!$result) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function addUserToContainer(string $userId, string $containerId) {
        $container = $this->getContainerById($containerId);

        $conn = $this->dbManager->getConnectionToDatabase($container->databaseName);

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
}

?>