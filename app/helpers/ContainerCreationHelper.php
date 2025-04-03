<?php

namespace App\Helpers;

use App\Entities\ContainerEntity;

/**
 * ContainerCreationHelper contains methods useful for container creation
 * 
 * @author Lukas Velek
 */
class ContainerCreationHelper {
    /**
     * Returns the table definitions for a container
     * 
     * @return array<string, array<string, string>> Container table definitions
     */
    public static function getContainerTableDefinitions() {
        return [
            'documents' => [
                'documentId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL',
                'authorUserId' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NULL',
                'binaryFileHash' => 'TEXT NULL',
                'status' => 'INT(4) NOT NULL',
                'classId' => 'VARCHAR(256) NOT NULL',
                'folderId' => 'VARCHAR(256) NULL',
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
                'title' => 'VARCHAR(256) NOT NULL',
                'isSystem' => 'INT(2) NOT NULL DEFAULT 0',
                'parentFolderId' => 'VARCHAR(256) NULL'
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
                'type' => 'INT(4) NOT NULL',
                'defaultValue' => 'TEXT NULL',
                'isRequired' => 'INT(2) NOT NULL DEFAULT 0'
            ],
            'documents_custom_metadata' => [
                'entryId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'documentId' => 'VARCHAR(256) NOT NULL',
                'metadataId' => 'VARCHAR(256) NOT NULL',
                'value' => 'TEXT NULL'
            ],
            'custom_metadata_list_values' => [
                'valueId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'metadataId' => 'VARCHAR(256) NOT NULL',
                'metadataKey' => 'INT(32) NOT NULL',
                'title' => 'TEXT NOT NULL'
            ],
            'processes' => [
                'processId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'documentId' => 'VARCHAR(256) NULL',
                'type' => 'VARCHAR(256) NOT NULL',
                'authorUserId' => 'VARCHAR(256) NOT NULL',
                'currentOfficerUserId' => 'VARCHAR(256) NULL',
                'workflowUserIds' => 'VARCHAR(256) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()',
                'status' => 'INT(4) NOT NULL DEFAULT 1',
                'currentOfficerSubstituteUserId' => 'VARCHAR(256) NULL'
            ],
            'process_types' => [
                'typeId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'typeKey' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'isEnabled' => 'INT(2) NOT NULL DEFAULT 1'
            ],
            'process_comments' => [
                'commentId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'processId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'description' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'transaction_log' => [
                'transactionId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'userId' => 'VARCHAR(256) NOT NULL',
                'callingMethod' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'grid_configuration' => [
                'configurationId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'gridName' => 'VARCHAR(256) NOT NULL',
                'columnConfiguration' => 'TEXT NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'process_metadata_history' => [
                'entryId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'processId' => 'VARCHAR(256) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'metadataName' => 'VARCHAR(256) NOT NULL',
                'oldValue' => 'VARCHAR(256) NULL',
                'newValue' => 'VARCHAR(256) NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'process_data' => [
                'entryId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'processId' => 'VARCHAR(256) NOT NULL',
                'data' => 'TEXT NOT NULL'
            ],
            'archive_folders' => [
                'folderId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'title' => 'VARCHAR(256) NOT NULL',
                'isSystem' => 'INT(2) NOT NULL DEFAULT 0',
                'parentFolderId' => 'VARCHAR(256) NULL',
                'status' => 'INT(4) NOT NULL DEFAULT 1'
            ],
            'archive_folder_document_relation' => [
                'relationId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'folderId' => 'VARCHAR(256) NOT NULL',
                'documentId' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'file_storage' => [
                'fileId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'filename' => 'VARCHAR(256) NOT NULL',
                'filepath' => 'TEXT NOT NULL',
                'filesize' => 'INT(32) NOT NULL',
                'userId' => 'VARCHAR(256) NOT NULL',
                'hash' => 'VARCHAR(256) NOT NULL',
                'dateCreated' => 'DATETIME NOT NULL DEFAULT current_timestamp()'
            ],
            'document_file_relation' => [
                'relationId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'documentId' => 'VARCHAR(256) NOT NULL',
                'fileId' => 'VARCHAR(256) NOT NULL'
            ],
            'process_metadata' => [
                'metadataId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'typeId' => 'VARCHAR(256) NOT NULL',
                'title' => 'VARCHAR(256) NOT NULL',
                'guiTitle' => 'VARCHAR(256) NOT NULL',
                'type' => 'INT(4) NOT NULL',
                'defaultValue' => 'TEXT NULL',
                'isRequired' => 'INT(2) NOT NULL DEFAULT 0'
            ],
            'process_metadata_list_values' => [
                'valueId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
                'metadataId' => 'VARCHAR(256) NOT NULL',
                'metadataKey' => 'INT(32) NOT NULL',
                'title' => 'TEXT NOT NULL'
            ]
        ];
    }

    /**
     * Returns container table index definitions
     * 
     * @return array<string, array<int, string>> Container table index definitions
     */
    public static function getContainerTableIndexDefinitions() {
        return [
            'documents' => [
                'folderId'
            ],
            'document_folder_group_relation' => [
                'folderId',
                'groupId',
                'canView'
            ],
            'document_folder_custom_metadata_relation' => [
                'customMetadataId',
                'folderId'
            ],
            'documents_custom_metadata' => [
                'documentId'
            ],
            'custom_metadata_list_values' => [
                'metadataId'
            ],
            'group_users_relation' => [
                'userId',
                'groupId'
            ],
            'group_rights_standard_operations' => [
                'groupId'
            ],
            'document_sharing' => [
                'authorUserId'
            ],
            'document_class_group_rights' => [
                'groupId',
                'classId',
                'canView'
            ],
            'grid_configuration' => [
                'gridName'
            ]
        ];
    }

    /**
     * Creates container configuration JSON
     * 
     * @param ContainerEntity $container Container entity
     */
    public static function createContainerConfigurationJson(ContainerEntity $container): string {
        $configuration = [
            'title' => $container->getTitle(),
            'description' => $container->getDescription(),
            'environment' => $container->getEnvironment(),
            'canShowContainerReferent' => $container->canShowContainerReferent(),
            'isInDistribution' => $container->isInDistribution()
        ];

        return json_encode($configuration);
    }
}

?>