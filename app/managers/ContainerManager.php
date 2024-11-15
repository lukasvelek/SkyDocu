<?php

namespace App\Managers;

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
    private ContainerRepository $containerRepository;
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

        return $containerId;
    }

    public function createNewContainerAsync(string $containerId) {
        $container = $this->containerRepository->getContainerById($containerId);

        if($container === null || $container === false) {
            throw new GeneralException('Container does not exist.');
        }

        $container = DatabaseRow::createFromDbRow($container);

        try {
            if(!$this->dbManager->createNewDatabase($container->databaseName)) {
                throw new GeneralException('Could not create database.');
            }

            $this->createNewContainerTables($container->databaseName);
            
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

        $groupIds = [
            SystemGroups::ADMINISTRATORS => $this->createIdCustomDb(EntityManager::C_GROUPS, $conn),
            SystemGroups::ALL_USERS => $this->createIdCustomDb(EntityManager::C_GROUPS, $conn)
        ];

        $folderIds = [
            'Default' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_FOLDERS, $conn)
        ];

        $classIds = [
            'Default' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASSES, $conn)
        ];
        
        $data = [
            [
                'table' => 'groups',
                'data' => [
                    'groupId' => $groupIds[SystemGroups::ADMINISTRATORS],
                    'title' => SystemGroups::ADMINISTRATORS
                ]
            ],
            [
                'table' => 'groups',
                'data' => [
                    'groupId' => $groupIds[SystemGroups::ALL_USERS],
                    'title' => SystemGroups::ALL_USERS
                ]
            ],
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
            ],
            [
                'table' => 'process_types',
                'data' => [
                    'typeId' => $this->createIdCustomDb(EntityManager::C_PROCESS_TYPES, $conn),
                    'typeKey' => 'shredding',
                    'title' => 'Document shredding',
                    'description' => 'Shred document'
                ]
            ],
        ];

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

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::CONTAINERS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function getContainerById(string $containerId) {
        $cache = $this->cacheFactory->getCache(CacheNames::CONTAINERS);

        $container = $cache->load($containerId, function() use ($containerId) {
            return $this->containerRepository->getContainerById($containerId);
        });

        if($container === null) {
            throw new NonExistingEntityException('Entity does not exist.');
        }
        
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
        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::CONTAINERS)) {
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
}

?>