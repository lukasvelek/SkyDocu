<?php

namespace App\Managers;

use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\Processes\InvoiceCustomMetadata;
use App\Constants\Container\StandaloneProcesses;
use App\Constants\Container\SystemGroups;
use App\Constants\ContainerStatus;
use App\Core\Caching\CacheNames;
use App\Core\DatabaseConnection;
use App\Core\DB\DatabaseManager;
use App\Core\DB\DatabaseRow;
use App\Core\FileManager;
use App\Core\HashManager;
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
    private DatabaseConnection $masterConn;

    public function __construct(Logger $logger, EntityManager $entityManager, ContainerRepository $containerRepository, DatabaseManager $dbManager, GroupManager $groupManager, DatabaseConnection $masterConn) {
        parent::__construct($logger, $entityManager);

        $this->containerRepository = $containerRepository;
        $this->dbManager = $dbManager;
        $this->groupManager = $groupManager;
        $this->masterConn = $masterConn;
    }

    public function createNewContainer(string $title, string $description, string $callingUserId, int $environment, bool $canShowReferent, int $status = ContainerStatus::NEW) {
        $containerId = $this->createId(EntityManager::CONTAINERS);
        $databaseName = 'sd_db_' . $containerId . '_' . HashManager::createHash(8, false);

        $data = [
            'containerId' => $containerId,
            'userId' => $callingUserId,
            'title' => $title,
            'description' => $description,
            'databaseName' => $databaseName,
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
            $this->updateContainerDbSchema($container->databaseName, $containerId);
            
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
            'Default' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_FOLDERS, $conn),
            'Invoices' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_FOLDERS, $conn)
        ];

        $classIds = [
            'Default' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASSES, $conn),
            'Invoices' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASSES, $conn)
        ];

        $metadataIds = [
            InvoiceCustomMetadata::COMPANY => $this->createIdCustomDb(EntityManager::C_CUSTOM_METADATA, $conn),
            InvoiceCustomMetadata::SUM => $this->createIdCustomDb(EntityManager::C_CUSTOM_METADATA, $conn),
            InvoiceCustomMetadata::INVOICE_NO => $this->createIdCustomDb(EntityManager::C_CUSTOM_METADATA, $conn),
            InvoiceCustomMetadata::SUM_CURRENCY => $this->createIdCustomDb(EntityManager::C_CUSTOM_METADATA, $conn)
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
                'table' => 'document_classes',
                'data' => [
                    'classId' => $classIds['Invoices'],
                    'title' => 'Invoices'
                ]
            ],
            [
                'table' => 'document_class_group_rights',
                'data' => [
                    'rightId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASS_GROUP_RIGHTS, $conn),
                    'groupId' => $groupIds[SystemGroups::ACCOUNTANTS],
                    'classId' => $classIds['Invoices'],
                    'canView' => 1,
                    'canCreate' => 1,
                    'canEdit' => 1
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
                'table' => 'document_folders',
                'data' => [
                    'folderId' => $folderIds['Invoices'],
                    'title' => 'Invoices',
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
                'table' => 'document_folder_group_relation',
                'data' => [
                    'relationId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_FOLDER_GROUP_RELATION, $conn),
                    'folderId' => $folderIds['Invoices'],
                    'groupId' => $groupIds[SystemGroups::ACCOUNTANTS],
                    'canView' => 1,
                    'canCreate' => 1,
                    'canEdit' => 1
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
                'table' => 'archive_folders',
                'data' => [
                    'folderId' => $folderIds['Default'],
                    'title' => 'Default',
                    'isSystem' => 1
                ]
            ],
            [
                'table' => 'custom_metadata',
                'data' => [
                    'metadataId' => $metadataIds['Invoices_SumCurrency'],
                    'title' => InvoiceCustomMetadata::SUM_CURRENCY,
                    'guiTitle' => InvoiceCustomMetadata::toString(InvoiceCustomMetadata::SUM_CURRENCY),
                    'type' => CustomMetadataTypes::SYSTEM_INVOICE_SUM_CURRENCY,
                    'isRequired' => 1
                ]
            ],
            [
                'table' => 'custom_metadata',
                'data' => [
                    'metadataId' => $metadataIds['Invoices_Sum'],
                    'title' => InvoiceCustomMetadata::SUM,
                    'guiTitle' => InvoiceCustomMetadata::toString(InvoiceCustomMetadata::SUM),
                    'type' => CustomMetadataTypes::NUMBER,
                    'isRequired' => 1
                ]
            ],
            [
                'table' => 'custom_metadata',
                'data' => [
                    'metadataId' => $metadataIds['Invoices_InvoiceNo'],
                    'title' => InvoiceCustomMetadata::INVOICE_NO,
                    'guiTitle' => InvoiceCustomMetadata::toString(InvoiceCustomMetadata::INVOICE_NO),
                    'type' => CustomMetadataTypes::TEXT,
                    'isRequired' => 1
                ]
            ],
            [
                'table' => 'custom_metadata',
                'data' => [
                    'metadataId' => $metadataIds['Invoices_Company'],
                    'title' => InvoiceCustomMetadata::COMPANY,
                    'guiTitle' => InvoiceCustomMetadata::toString(InvoiceCustomMetadata::COMPANY),
                    'type' => CustomMetadataTypes::SYSTEM_INVOICE_COMPANIES,
                    'isRequired' => 1
                ]
            ],
        ];

        foreach($metadataIds as $title => $id) {
            $data[] = [
                'table' => 'document_folder_custom_metadata_relation',
                'data' => [
                    'relationId' => $this->createIdCustomDb(EntityManager::C_CUSTOM_METADATA_FOLDER_RELATION, $conn),
                    'customMetadataId' => $id,
                    'folderId' => $folderIds['Invoices']
                ]
            ];
        }

        $standaloneProcessIds = [];
        foreach(StandaloneProcesses::getAll() as $key => $title) {
            if(StandaloneProcesses::isDisabled($key)) continue;

            $standaloneProcessIds[$key] = $this->createIdCustomDb(EntityManager::C_PROCESS_TYPES, $conn);

            $data[] = [
                'table' => 'process_types',
                'data' => [
                    'typeId' => $standaloneProcessIds[$key],
                    'typeKey' => $key,
                    'title' => $title,
                    'description' => StandaloneProcesses::getDescription($key)
                ]
            ];
        }

        $data[] = [
            'table' => 'process_metadata',
            'data' => [
                'metadataId' => $this->createIdCustomDb(EntityManager::C_PROCESS_CUSTOM_METADATA, $conn),
                'typeId' => $standaloneProcessIds[StandaloneProcesses::INVOICE],
                'title' => 'companies',
                'guiTitle' => 'Companies',
                'type' => CustomMetadataTypes::ENUM,
                'isRequired' => '1'
            ]
        ];

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

    private function createNewContainerTables(string $dbName, array $tableDefinitions = []) {
        if(empty($tableDefinitions)) {
            $tables = ContainerCreationHelper::getContainerTableDefinitions();
        } else {
            $tables = $tableDefinitions;
        }

        foreach($tables as $name => $definition) {
            if(!$this->dbManager->createTable($name, $definition, $dbName)) {
                throw new GeneralException('Could not create database table.');
            }
        }
    }

    private function createContainerTablesIndexes(string $dbName, array $indexDefinitions = []) {
        if(empty($indexDefinitions)) {
            $indexes = ContainerCreationHelper::getContainerTableIndexDefinitions();
        } else {
            $indexes = $indexDefinitions;
        }

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

    private function updateContainerDbSchema(string $dbName, string $containerId) {
        try {
            $conn = $this->dbManager->getConnectionToDatabase($dbName);
        } catch(AException $e) {
            throw new GeneralException('Could not establish connection to the container database.');
        }

        $sqlScripts = [];
        $files = FileManager::getFilesInFolder(APP_ABSOLUTE_DIR . 'data\\db\\schema');
        foreach($files as $file => $fileFullpath) {
            if($file != "." && $file != "..") {
                if(str_starts_with($file, 'u') && str_ends_with($file, '.php')) {
                    $sqlScripts[$file] = $fileFullpath;
                }
            }
        }

        $this->logger->info('Found ' . count($sqlScripts) . ' DB schema updates.', __METHOD__);

        foreach($sqlScripts as $scriptName => $script) {
            $php = file_get_contents($script);

            $schema = (int)(substr($scriptName, 1, -strlen('.php')));

            $php = substr($php, strlen('<?php'));
            $php = substr($php, 0, -strlen('?>'));

            $result = eval($php);

            if(!empty($result['tables'])) {
                $this->createNewContainerTables($dbName, $result['tables']);
            }
            if(!empty($result['indexes'])) {
                $this->createContainerTablesIndexes($dbName, $result['indexes']);
            }
            if(!empty($result['data'])) {
                foreach($result['data'] as $tableName => $contents) {
                    foreach($contents as $content) {
                        $sql = 'INSERT INTO ' . $tableName . '(' . implode(', ', array_keys($content)) . ')';
                        $sql .= ' VALUES (\'' . implode('\', \'', $content) . '\')';

                        $conn->query($sql);
                    }
                }
            }

            $this->updateDbSchema($containerId, $schema);

            $this->logger->info('Updated container database schema to ' . $schema . '.', __METHOD__);
        }
    }

    private function updateDbSchema(string $containerId, int $schema) {
        $sql = "UPDATE containers SET dbSchema = " . $schema . " WHERE containerId = '" . $containerId . "';";

        $this->masterConn->query($sql);
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

    public function deleteContainer(string $containerId, bool $isRequest = false) {
        $container = $this->getContainerById($containerId);
        
        if(!$isRequest) {
            // Drop container database with all data
            try {
                $this->dbManager->dropDatabase($container->databaseName);
            } catch(AException $e) {}

            // Remove group and memberships
            $group = null;
            try {
                $group = $this->groupManager->getGroupByTitle($container->title . ' - users');
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

        $this->groupManager->createNewGroup($container->title . ' - users', [$callingUserId], $containerId);

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::NAVBAR_CONTAINER_SWITCH_USER_MEMBERSHIPS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }
}

?>