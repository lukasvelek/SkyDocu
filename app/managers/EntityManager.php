<?php

namespace App\Managers;

use App\Core\DatabaseConnection;
use App\Core\HashManager;
use App\Logger\Logger;
use App\Repositories\ContentRepository;

/**
 * EntityManager contains useful methods for working with entities saved to database
 * 
 * @author Lukas Velek
 */
class EntityManager extends AManager {
    public const USERS = 'users';
    public const TRANSACTIONS = 'transaction_log';
    public const GRID_EXPORTS = 'grid_exports';
    public const GROUPS = 'groups';
    public const GROUP_USERS = 'group_users';
    public const CONTAINERS = 'containers';
    public const CONTAINER_CREATION_STATUS = 'container_creation_status';
    public const CONTAINER_STATUS_HISTORY = 'container_status_history';
    public const SERVICE_HISTORY = 'system_services_history';
    public const CONTAINER_USAGE_STATISTICS = 'container_usage_statistics';

    public const C_GROUPS = 'groups';
    public const C_DOCUMENT_CLASSES = 'document_classes';
    public const C_DOCUMENT_CLASS_GROUP_RIGHTS = 'document_class_group_rights';
    public const C_DOCUMENT_FOLDERS = 'document_folders';
    public const C_DOCUMENT_FOLDER_GROUP_RELATION = 'document_folder_group_relation';
    public const C_GROUP_STANDARD_OPERATION_RIGHTS = 'group_rights_standard_operations';
    public const C_PROCESS_TYPES = 'process_types';
    public const C_DOCUMENTS = 'documents';
    public const C_DOCUMENTS_CUSTOM_METADATA = 'documents_custom_metadata';
    public const C_GROUP_USERS_RELATION = 'group_users_relation';
    public const C_CUSTOM_METADATA = 'custom_metadata';
    public const C_CUSTOM_METADATA_FOLDER_RELATION = 'document_folder_custom_metadata_relation';
    public const C_CUSTOM_METADATA_LIST_VALUES = 'custom_metadata_list_values';
    public const C_GRID_CONFIGURATION = 'grid_configuration';
    public const C_PROCESSES = 'processes';
    public const C_PROCESS_COMMENTS = 'process_comments';
    public const C_PROCESS_METADATA_HISTORY = 'process_metadata_history';
    public const C_PROCESS_DATA = 'process_data';

    private const __MAX__ = 100;

    private ContentRepository $cr;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     * @param ContentRepository $cr ContentRepository instance
     */
    public function __construct(Logger $logger, ContentRepository $cr) {
        parent::__construct($logger, null);

        $this->cr = $cr;
    }

    public function generateEntityIdCustomDb(string $category, DatabaseConnection $customConn) {
        $unique = true;
        $run = true;

        $entityId = null;
        $x = 0;
        while($run) {
            $entityId = HashManager::createEntityId();

            $primaryKeyName = $this->getPrimaryKeyNameByCategory($category);

            $cr = new ContentRepository($customConn, $this->logger);
            $unique = $cr->checkIdIsUnique($category, $primaryKeyName, $entityId);

            if($unique || $x >= self::__MAX__) {
                $run = false;
                break;
            }

            $x++;
        }

        return $entityId;
    }

    /**
     * Generates unique entity ID for given category
     * 
     * @param string $category (see constants in \App\Managers\EntityManager)
     * @return null|string Generated unique entity ID or null
     */
    public function generateEntityId(string $category) {
        $unique = true;
        $run = true;

        $entityId = null;
        $x = 0;
        while($run) {
            $entityId = HashManager::createEntityId();

            $primaryKeyName = $this->getPrimaryKeyNameByCategory($category);

            $unique = $this->cr->checkIdIsUnique($category, $primaryKeyName, $entityId);

            if($unique || $x >= self::__MAX__) {
                $run = false;
                break;
            }

            $x++;
        }

        return $entityId;
    }

    /**
     * Returns primary key for given category (database table)
     * 
     * @return string Primary key
     */
    private function getPrimaryKeyNameByCategory(string $category) {
        return match($category) {
            self::USERS => 'userId',
            self::TRANSACTIONS => 'transactionId',
            self::GRID_EXPORTS => 'exportId',
            self::GROUPS => 'groupId',
            self::GROUP_USERS => 'groupUserId',
            self::CONTAINERS => 'containerId',
            self::CONTAINER_CREATION_STATUS => 'statusId',
            self::CONTAINER_STATUS_HISTORY => 'historyId',
            self::SERVICE_HISTORY => 'historyId',
            self::CONTAINER_USAGE_STATISTICS => 'entryId',

            self::C_GROUPS => 'groupId',
            self::C_DOCUMENT_CLASSES => 'classId',
            self::C_DOCUMENT_CLASS_GROUP_RIGHTS => 'rightId',
            self::C_DOCUMENT_FOLDERS => 'folderId',
            self::C_DOCUMENT_FOLDER_GROUP_RELATION => 'relationId',
            self::C_GROUP_STANDARD_OPERATION_RIGHTS => 'rightId',
            self::C_PROCESS_TYPES => 'typeId',
            self::C_DOCUMENTS => 'documentId',
            self::C_DOCUMENTS_CUSTOM_METADATA => 'entryId',
            self::C_GROUP_USERS_RELATION => 'relationId',
            self::C_CUSTOM_METADATA => 'metadataId',
            self::C_CUSTOM_METADATA_FOLDER_RELATION => 'relationId',
            self::C_CUSTOM_METADATA_LIST_VALUES => 'valueId',
            self::C_GRID_CONFIGURATION => 'configurationId',
            self::C_PROCESSES => 'processId',
            self::C_PROCESS_COMMENTS => 'commentId',
            self::C_PROCESS_METADATA_HISTORY => 'entryId',
            self::C_PROCESS_DATA => 'entryId'
        };
    }
}

?>