<?php

namespace App\Managers;

use App\Core\DB\DatabaseManager;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Helpers\ContainerCreationHelper;
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
        $databaseName = 'sd_db_' . $containerId;

        if(!$this->containerRepository->createNewContainer($containerId, $callingUserId, $title, $description, $databaseName)) {
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

        $classIds = [
            'Default' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASSES, $conn)
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
                'classId' => $classIds['Default'],
                'title' => 'Default'
            ],
            'document_class_group_rights' => [
                'rightId' => $this->createIdCustomDb(EntityManager::C_DOCUMENT_CLASS_GROUP_RIGHTS, $conn),
                'groupId' => $groupIds['All users'],
                'classId' => $classIds['Default'],
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
                'typeKey' => 'shredding',
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

    public function getContainerById(string $containerId) {
        $container = $this->containerRepository->getContainerById($containerId);

        if($container === null) {
            throw new NonExistingEntityException('Entity does not exist.');
        }
        
        return DatabaseRow::createFromDbRow($container);
    }
}

?>