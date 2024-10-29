<?php

namespace App\Managers;

use App\Core\DB\DatabaseManager;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\ContainerRepository;

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

    public function createNewContainer(string $title, string $description, string $callingUserId) {
        $containerId = $this->createId(EntityManager::CONTAINERS);
        $databaseName = 'SD_db_' . $containerId;

        if(!$this->containerRepository->createNewContainer($containerId, $callingUserId, $title, $description, $databaseName)) {
            throw new GeneralException('Could not create a new container.');
        }

        $statusId = $this->createId(EntityManager::CONTAINER_CREATION_STATUS);
        if(!$this->containerRepository->createNewCreationStatusEntry($statusId, $containerId)) {
            throw new GeneralException('Could not queue container for background creation.');
        }

        $this->groupManager->createNewGroup($title . ' - users', [$callingUserId]);

        return $containerId;
    }

    public function createNewContainerAsync(string $containerId) {
        $container = $this->containerRepository->getContainerById($containerId);

        if($container === null || $container === false) {
            throw new GeneralException('Container does not exist.');
        }

        $container = DatabaseRow::createFromDbRow($container);

        try {
            $this->createNewContainerTables($container->databaseName);
            
            $exceptions = [];
            $this->insertNewContainerDefaultDataAsync($containerId, $container->databaseName, $exceptions);
        } catch(AException $e) {
            throw $e;
        }
    }

    private function insertNewContainerDefaultDataAsync(string $containerId, string $dbName, array &$exceptions) {
        try {
            $conn = $this->dbManager->getConnectionToDatabase($dbName);
        } catch(AException $e) {
            throw new GeneralException('Could not establish connection to the container database.');
        }

        $groupIds = [
            'Administrators' => $this->createIdCustomDb(EntityManager::C_GROUPS, $conn),
            'All users' => $this->createIdCustomDb(EntityManager::C_GROUPS, $conn)
        ];

        $folderIds = [
            'Default' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_FOLDERS, $conn)
        ];
        
        $data = [
            'groups' => [
                'groupId' => $groupIds['Administrators'],
                'title' => 'Administrators'
            ],
            'groups' => [
                'groupId' => $groupIds['All users'],
                'title' => 'All users'
            ],
            'document_classes' => [
                'classId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASSES, $conn),
                'title' => 'Default'
            ],
            'document_class_group_rights' => [
                'rightId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASS_GROUP_RIGHTS, $conn),
                'groupId' => $groupIds['All users'],
                'canView' => 1,
                'canCreate' => 1
            ],
            'document_class_group_rights' => [
                'rightId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASS_GROUP_RIGHTS, $conn),
                'groupId' => $groupIds['Administrators'],
                'canView' => 1,
                'canCreate' => 1,
                'canEdit' => 1,
                'canDelete' => 1
            ],
            'document_folders' => [
                'folderId' => $folderIds['Default'],
                'title' => 'Default'
            ],
            'document_folder_group_relation' => [
                'relationId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_FOLDER_GROUP_RELATION, $conn),
                'folderId' => $folderIds['Default'],
                'groupId' => $groupIds['All users'],
                'canView' => 1,
                'canCreate' => 1
            ],
            'document_folder_group_relation' => [
                'relationId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_FOLDER_GROUP_RELATION, $conn),
                'folderId' => $folderIds['Default'],
                'groupId' => $groupIds['Administrators'],
                'canView' => 1,
                'canCreate' => 1,
                'canEdit' => 1,
                'canDelete' => 1
            ],
            'group_rights_standard_operations' => [
                'rightId' => $this->createIdCustomDb(EntityManager::C_GROUP_STANDARD_OPERATION_RIGHTS, $conn),
                'groupId' => $groupIds['Administrators'],
                'canShareDocuments' => 1,
                'canExportDocuments' => 1,
                'canViewDocumentHistory' => 1
            ],
            'process_types' => [
                'typeId' => $this->createIdCustomDb(EntityManager::C_PROCESS_TYPES, $conn),
                'key' => 'shredding',
                'title' => 'Document shredding',
                'description' => 'Shred document'
            ]
        ];

        foreach($data as $tableName => $values) {
            try {
                $this->dbManager->insertDataToTable($tableName, $values, $dbName);
            } catch(AException $e) {
                $exceptions[$tableName] = $e;
                continue;
            }
        }
    }

    private function createNewContainerTables(string $dbName) {
        $tables = [
            'documents' => [
                'documentId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL',
                'authorUserId' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NULL',
                'binaryFileHash' => 'TEXT NULL',
                'status' => 'INT(4) NOT NULL',
                'classId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'dateModified' => 'DATETIME NULL'
            ],
            'document_change_history' => [
                'historyEntryId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'documentId' => 'VARCHAR(256) NOT NULL',
                'authorUserId' => 'VARCHAR(256) NOT NULL',
                'metadataName' => 'VARCHAR(256) NOT NULL',
                'oldValue' => 'TEXT NULL',
                'newValue' => 'TEXT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'document_sharing' => [
                'sharingId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'documentId' => 'VARCHAR(256) NOT NULL',
                'authorUserId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'dateValidUntil' => 'DATETIME NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'document_classes' => [
                'classId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL'
            ],
            'document_class_group_rights' => [
                'rightId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'groupId' => 'VARCHAR(256) NOT NULL',
                'classId' => 'VARCHAR(256) NOT NULL',
                'canView' => 'INT(2) NOT NULL DEFAULT 0',
                'canCreate' => 'INT(2) NOT NULL DEFAULT 0',
                'canEdit' => 'INT(2) NOT NULL DEFAULT 0',
                'canDelete' => 'INT(2) NOT NULL DEFAULT 0'
            ],
            'document_folders' => [
                'folderId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL'
            ],
            'document_folder_custom_metadata_relation' => [
                'relationId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'customMetadataId' => 'VARCHAR(256) NOT NULL',
                'folderId' => 'VARCHAR(256) NOT NULL'
            ],
            'document_folder_group_relation' => [
                'relationId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'folderId' => 'VARCHAR(256) NOT NULL',
                'groupId' => 'VARCHAR(256) NOT NULL',
                'canView' => 'INT(2) NOT NULL DEFAULT 0',
                'canCreate' => 'INT(2) NOT NULL DEFAULT 0',
                'canEdit' => 'INT(2) NOT NULL DEFAULT 0',
                'canDelete' => 'INT(2) NOT NULL DEFAULT 0'
            ],
            'groups' => [
                'groupId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL'
            ],
            'group_users_relation' => [
                'relationId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'groupId' => 'VARCHAR(256) NOT NULL'
            ],
            'group_rights_standard_operations' => [
                'rightId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'groupId' => 'VARCHAR(256) NOT NULL',
                'canShareDocuments' => 'INT(2) NOT NULL DEFAULT 0',
                'canExportDocuments' => 'INT(2) NOT NULL DEFAULT 0',
                'canViewDocumentHistory' => 'INT(2) NOT NULL DEFAULT 0'
            ],
            'custom_metadata' => [
                'metadataId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL',
                'guiTitle' => 'VARCHAR(256) NOT NULL',
                'type' => 'INT(4) NOT NULL'
            ],
            'documents_custom_metadata' => [
                'entryId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'documentId' => 'VARCHAR(256) NOT NULL',
                'metadataId' => 'VARCHAR(256) NOT NULL',
                'value' => 'TEXT NOT NULL'
            ],
            'custom_metadata_list_values' => [
                'valueId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'metadataId' => 'VARCHAR(256) NOT NULL',
                'key' => 'INT(32) NOT NULL',
                'title' => 'TEXT NOT NULL'
            ],
            'processes' => [
                'processId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'documentId' => 'VARCHAR(256) NOT NULL',
                'type' => 'VARCHAR(256) NOT NULL',
                'authorUserId' => 'VARCHAR(256) NOT NULL',
                'currentOfficerUserId' => 'VARCHAR(256) NULL',
                'workflowUserIds' => 'VARCHAR(256) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'process_types' => [
                'typeId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'key' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL'
            ],
            'process_comments' => [
                'commentId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'processId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ]
        ];

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

        $container = $this->containerRepository->getContainerById($containerId);
        if($container === null || $container === false) {
            throw new GeneralException('Container does not exist.');
        }

        $container = DatabaseRow::createFromDbRow($container);

        if(!$this->containerRepository->createNewStatusHistoryEntry($historyId, $containerId, $callingUserId, $description, $container->status, $newStatus)) {
            throw new GeneralException('Could not create new status history change entry.');
        }

        if(!$this->containerRepository->updateContainer($containerId, [
            'status' => $newStatus
        ])) {
            throw new GeneralException('Could not change status.');
        }
    }
}

?>