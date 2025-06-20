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
    public const SYSTEM_SERVICES = 'system_services';
    public const SERVICE_HISTORY = 'system_services_history';
    public const CONTAINER_USAGE_STATISTICS = 'container_usage_statistics';
    public const CONTAINER_INVITES = 'container_invites';
    public const CONTAINER_INVITE_USAGE = 'container_invite_usage';
    public const USER_ABSENCE = 'user_absence';
    public const USER_SUBSTITUTES = 'user_substitutes';
    public const CONTAINER_DATABASES = 'container_databases';
    public const CONTAINER_DATABASE_TABLES = 'container_database_tables';
    public const CONTAINER_DATABASE_TABLE_COLUMNS = 'container_database_table_columns';
    public const PROCESSES = 'processes';
    public const PROCESSES_UNIQUE = 'processes';
    public const JOB_QUEUE = 'job_queue';
    public const JOB_QUEUE_PROCESSING_HISTORY = 'job_queue_processing_history';

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
    public const C_PROCESS_INSTANCES = 'process_instances';
    public const C_DOCUMENT_SHARING = 'document_sharing';
    public const C_ARCHIVE_FOLDERS = 'archive_folders';
    public const C_ARCHIVE_FOLDER_DOCUMENT_RELATION = 'archive_folder_document_relation';
    public const C_FILE_STORAGE = 'file_storage';
    public const C_DOCUMENT_FILE_RELATION = 'document_file_relation';
    public const C_PROCESS_CUSTOM_METADATA_VALUES = 'process_metadata_values';
    public const C_PROCESS_CUSTOM_METADATA = 'process_metadata';
    public const C_EXTERNAL_SYSTEMS = 'external_systems';
    public const C_EXTERNAL_SYSTEM_LOG = 'external_system_log';
    public const C_EXTERNAL_SYSTEM_TOKENS = 'external_system_tokens';
    public const C_EXTERNAL_SYSTEM_RIGHTS = 'external_system_rights';
    public const C_PROPERTY_ITEMS_USER_RELATION = 'property_items_user_relation';
    public const C_PROCESS_FILE_RELATION = 'process_file_relation';

    private const __MAX__ = 100;

    private ContentRepository $contentRepository;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     * @param ContentRepository $contentRepository ContentRepository instance
     */
    public function __construct(Logger $logger, ContentRepository $contentRepository) {
        parent::__construct($logger, null);

        $this->contentRepository = $contentRepository;
    }

    public function generateEntityIdCustomDb(string $category, DatabaseConnection $customConn) {
        $unique = true;
        $run = true;

        $entityId = null;
        $x = 0;
        while($run) {
            $entityId = HashManager::createEntityId();

            $primaryKeyName = $this->getPrimaryKeyNameByCategory($category, ($customConn->getName() != DB_MASTER_NAME));

            $cr = new ContentRepository($customConn, $this->logger, $this->contentRepository->transactionLogRepository);
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

            $primaryKeyName = $this->getPrimaryKeyNameByCategory($category, ($this->contentRepository->conn->getName() != DB_MASTER_NAME));

            $unique = $this->contentRepository->checkIdIsUnique($category, $primaryKeyName, $entityId);

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
     * @param string $category Database table name
     * @param bool $isContainer Is container or master?
     * 
     * @return string Primary key
     */
    public static function getPrimaryKeyNameByCategory(string $category, bool $isContainer = false) {
        if($isContainer) {
            return match($category) {
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
                self::C_PROCESS_INSTANCES => 'instanceId',
                self::C_DOCUMENT_SHARING => 'sharingId',
                self::C_ARCHIVE_FOLDERS => 'folderId',
                self::C_ARCHIVE_FOLDER_DOCUMENT_RELATION => 'relationId',
                self::C_FILE_STORAGE => 'fileId',
                self::C_DOCUMENT_FILE_RELATION => 'relationId',
                self::C_PROCESS_CUSTOM_METADATA_VALUES => 'valueId',
                self::C_PROCESS_CUSTOM_METADATA => 'metadataId',
                self::C_EXTERNAL_SYSTEMS => 'systemId',
                self::C_EXTERNAL_SYSTEM_LOG => 'entryId',
                self::C_EXTERNAL_SYSTEM_TOKENS => 'tokenId',
                self::C_EXTERNAL_SYSTEM_RIGHTS => 'rightId',
                self::C_PROPERTY_ITEMS_USER_RELATION => 'relationId',
                self::C_PROCESS_FILE_RELATION => 'relationId',

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
                self::CONTAINER_INVITES => 'inviteId',
                self::CONTAINER_INVITE_USAGE => 'entryId',
                self::USER_ABSENCE => 'absenceId',
                self::USER_SUBSTITUTES => 'entryId',
                self::CONTAINER_DATABASES => 'entryId',
                self::CONTAINER_DATABASE_TABLES => 'entryId',
                self::CONTAINER_DATABASE_TABLE_COLUMNS => 'entryId',
                self::SYSTEM_SERVICES => 'serviceId',
                self::PROCESSES => 'processId',
                self::PROCESSES_UNIQUE => 'uniqueProcessId',
                self::JOB_QUEUE => 'jobId',
                self::JOB_QUEUE_PROCESSING_HISTORY => 'entryId'
            };
        } else {
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
                self::CONTAINER_INVITES => 'inviteId',
                self::CONTAINER_INVITE_USAGE => 'entryId',
                self::USER_ABSENCE => 'absenceId',
                self::USER_SUBSTITUTES => 'entryId',
                self::CONTAINER_DATABASES => 'entryId',
                self::CONTAINER_DATABASE_TABLES => 'entryId',
                self::CONTAINER_DATABASE_TABLE_COLUMNS => 'entryId',
                self::SYSTEM_SERVICES => 'serviceId',
                self::PROCESSES => 'processId',
                self::PROCESSES_UNIQUE => 'uniqueProcessId',
                self::JOB_QUEUE => 'jobId',
                self::JOB_QUEUE_PROCESSING_HISTORY => 'entryId'
            };
        }
    }

    /**
     * Checks if given value is unique in given column
     * 
     * @param string $table Table name
     * @param string $columnName Column name
     * @param string $value Value
     */
    public function checkUniqueValueInColumn(string $table, string $columnName, string $value): bool {
        return $this->contentRepository->checkValueIsUnique($table, $columnName, $value);
    }
}

?>