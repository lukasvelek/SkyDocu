<?php

namespace App\Data\Db\Migrations\Containers;

use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\Processes\InvoiceCustomMetadata;
use App\Constants\Container\StandaloneProcesses;
use App\Constants\Container\SystemGroups;
use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;
use App\Exceptions\GeneralException;
use App\Managers\EntityManager;

/**
 * This is the initial migration that defines the database schema for each SkyDocu container.
 * 
 * Here are defined all tables that are necessary for each container.
 * 
 * @author Lukas Velek
 * @version 1.0 from 03/15/2025
 */
class migration_2025_03_15_0001_initial extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('documents')
            ->primaryKey('documentId')
            ->varchar('title')
            ->varchar('authorUserId')
            ->text('description', true)
            ->text('binaryFileHash', true)
            ->integer('status', 4)
            ->varchar('classId')
            ->varchar('folderId', 256, true)
            ->datetimeAuto('dateCreated')
            ->datetime('dateModified', true)
            ->index(['folderId']);

        $table->create('document_change_history')
            ->primaryKey('historyEntryId')
            ->varchar('documentId')
            ->varchar('authorUserId')
            ->varchar('metadataName')
            ->text('oldValue', true)
            ->text('newValue', true)
            ->datetimeAuto('dateCreated')
            ->index(['documentId']);

        $table->create('document_sharing')
            ->primaryKey('sharingId')
            ->varchar('documentId')
            ->varchar('authorUserId')
            ->varchar('userId')
            ->datetime('dateValidUntil')
            ->datetimeAuto('dateCreated')
            ->index(['documentId']);

        $table->create('document_classes')
            ->primaryKey('classId')
            ->varchar('title');

        $table->create('document_class_group_rights')
            ->primaryKey('rightId')
            ->varchar('groupId')
            ->varchar('classId')
            ->bool('canView')
            ->default('canView', 0)
            ->bool('canCreate')
            ->default('canCreate', 0)
            ->bool('canEdit')
            ->default('canEdit', 0)
            ->bool('canDelete')
            ->default('canDelete', 0)
            ->index(['classId', 'groupId']);

        $table->create('document_folders')
            ->primaryKey('folderId')
            ->varchar('title')
            ->bool('isSystem')
            ->default('isSystem', 0)
            ->varchar('parentFolderId', 256, true)
            ->index(['parentFolderId']);

        $table->create('document_folder_custom_metadata_relation')
            ->primaryKey('relationId')
            ->varchar('customMetadataId')
            ->varchar('folderId')
            ->index(['customMetadataId', 'folderId']);

        $table->create('document_folder_group_relation')
            ->primaryKey('relationId')
            ->varchar('folderId')
            ->varchar('groupId')
            ->bool('canView')
            ->default('canView', 0)
            ->bool('canCreate')
            ->default('canCreate', 0)
            ->bool('canEdit')
            ->default('canEdit', 0)
            ->bool('canDelete')
            ->default('canDelete', 0)
            ->index(['folderId', 'groupId']);

        $table->create('groups')
            ->primaryKey('groupId')
            ->varchar('title');

        $table->create('group_users_relation')
            ->primaryKey('relationId')
            ->varchar('userId')
            ->varchar('groupId')
            ->index(['userId', 'groupId']);

        $table->create('group_rights_standard_operations')
            ->primaryKey('rightId')
            ->varchar('groupId')
            ->bool('canShareDocuments')
            ->default('canShareDocuments', 0)
            ->bool('canExportDocuments')
            ->default('canExportDocuments', 0)
            ->bool('canViewDocumentHistory')
            ->default('canViewDocumentHistory', 0)
            ->index(['groupId']);

        $table->create('custom_metadata')
            ->primaryKey('metadataId')
            ->varchar('title')
            ->varchar('guiTitle')
            ->integer('type', 4)
            ->text('defaultValue', true)
            ->bool('isRequired')
            ->default('isRequired', 0);

        $table->create('documents_custom_metadata')
            ->primaryKey('entryId')
            ->varchar('documentId')
            ->varchar('metadataId')
            ->text('value', true)
            ->index(['documentId']);

        $table->create('custom_metadata_list_values')
            ->primaryKey('valueId')
            ->varchar('metadataId')
            ->integer('metadataKey')
            ->text('title')
            ->index(['metadataId']);

        $table->create('transaction_log')
            ->primaryKey('transactionId')
            ->varchar('userId')
            ->text('callingMethod')
            ->datetimeAuto('dateCreated');

        $table->create('grid_configuration')
            ->primaryKey('configurationId')
            ->varchar('gridName')
            ->text('columnConfiguration')
            ->datetimeAuto('dateCreated')
            ->index(['gridName']);

        $table->create('archive_folders')
            ->primaryKey('folderId')
            ->varchar('title')
            ->bool('isSystem')
            ->default('isSystem', 1)
            ->varchar('parentFolderId', 256, true)
            ->integer('status', 4)
            ->default('status', 1)
            ->index(['parentFolderId']);

        $table->create('archive_folder_document_relation')
            ->primaryKey('relationId')
            ->varchar('folderId')
            ->varchar('documentId')
            ->datetimeAuto('dateCreated')
            ->index(['folderId', 'documentId']);

        $table->create('file_storage')
            ->primaryKey('fileId')
            ->varchar('filename')
            ->text('filepath')
            ->integer('filesize')
            ->varchar('userId')
            ->varchar('hash')
            ->datetimeAuto('dateCreated');

        $table->create('document_file_relation')
            ->primaryKey('relationId')
            ->varchar('documentId')
            ->varchar('fileId')
            ->index(['documentId']);

        $table->create('process_metadata')
            ->primaryKey('metadataId')
            ->varchar('typeId')
            ->varchar('title')
            ->varchar('guiTitle')
            ->integer('type', 4)
            ->text('defaultValue', true)
            ->bool('isRequired')
            ->default('isRequired', 0);

        $table->create('process_metadata_list_values')
            ->primaryKey('valueId')
            ->varchar('metadataId')
            ->integer('metadataKey')
            ->text('title')
            ->index(['metadataId']);

        return $table;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $users = $this->getContainerGroupUsers();

        $groupIds = [];
        foreach(SystemGroups::getAll() as $value => $text) {
            $groupIds[$value] = $this->getId(EntityManager::C_GROUPS);
        }

        $folderIds = [
            'Default' => $this->getId(EntityManager::C_DOCUMENT_FOLDERS),
            'Invoices' => $this->getId(EntityManager::C_DOCUMENT_FOLDERS)
        ];

        $classIds = [
            'Default' => $this->getId(EntityManager::C_DOCUMENT_CLASSES),
            'Invoices' => $this->getId(EntityManager::C_DOCUMENT_CLASSES)
        ];

        $metadataIds = [
            InvoiceCustomMetadata::COMPANY => $this->getId(EntityManager::C_CUSTOM_METADATA),
            InvoiceCustomMetadata::SUM => $this->getId(EntityManager::C_CUSTOM_METADATA),
            InvoiceCustomMetadata::INVOICE_NO => $this->getId(EntityManager::C_CUSTOM_METADATA),
            InvoiceCustomMetadata::SUM_CURRENCY => $this->getId(EntityManager::C_CUSTOM_METADATA),
        ];

        $seed->seed(EntityManager::C_DOCUMENT_CLASSES)
            ->add([
                'classId' => $classIds['Default'],
                'title' => 'Default'
            ])
            ->add([
                'classId' => $classIds['Invoices'],
                'title' => 'Invoices'
            ]);

        $seed->seed(EntityManager::C_DOCUMENT_CLASS_GROUP_RIGHTS)
            ->add([
                'rightId' => $this->getId(EntityManager::C_DOCUMENT_CLASS_GROUP_RIGHTS),
                'groupId' => $groupIds[SystemGroups::ACCOUNTANTS],
                'classId' => $classIds['Invoices'],
                'canView' => 1,
                'canCreate' => 1,
                'canEdit' => 1
            ])
            ->add([
                'rightId' => $this->getId(EntityManager::C_DOCUMENT_CLASS_GROUP_RIGHTS),
                'groupId' => $groupIds[SystemGroups::ALL_USERS],
                'classId' => $classIds['Default'],
                'canView' => 1,
                'canCreate' => 1
            ])
            ->add([
                'rightId' => $this->getId(EntityManager::C_DOCUMENT_CLASS_GROUP_RIGHTS),
                'groupId' => $groupIds[SystemGroups::ADMINISTRATORS],
                'classId' => $classIds['Default'],
                'canView' => 1,
                'canCreate' => 1,
                'canEdit' => 1,
                'canDelete' => 1
            ]);

        $seed->seed(EntityManager::C_DOCUMENT_FOLDERS)
            ->add([
                'folderId' => $folderIds['Default'],
                'title' => 'Default',
                'isSystem' => 1
            ])
            ->add([
                'folderId' => $folderIds['Invoices'],
                'title' => 'Invoices',
                'isSystem' => 1
            ]);

        $seed->seed(EntityManager::C_DOCUMENT_FOLDER_GROUP_RELATION)
            ->add([
                'relationId' => $this->getId(EntityManager::C_DOCUMENT_FOLDER_GROUP_RELATION),
                'folderId' => $folderIds['Default'],
                'groupId' => $groupIds[SystemGroups::ALL_USERS],
                'canView' => 1,
                'canCreate' => 1
            ])
            ->add([
                'relationId' => $this->getId(EntityManager::C_DOCUMENT_FOLDER_GROUP_RELATION),
                'folderId' => $folderIds['Default'],
                'groupId' => $groupIds[SystemGroups::ADMINISTRATORS],
                'canView' => 1,
                'canCreate' => 1,
                'canEdit' => 1,
                'canDelete' => 1
            ])
            ->add([
                'relationId' => $this->getId(EntityManager::C_DOCUMENT_FOLDER_GROUP_RELATION),
                'folderId' => $folderIds['Invoices'],
                'groupId' => $groupIds[SystemGroups::ACCOUNTANTS],
                'canView' => 1,
                'canCreate' => 1,
                'canEdit' => 1
            ]);

        $seed->seed(EntityManager::C_GROUP_STANDARD_OPERATION_RIGHTS)
            ->add([
                'rightId' => $this->getId(EntityManager::C_GROUP_STANDARD_OPERATION_RIGHTS),
                'groupId' => $groupIds[SystemGroups::ADMINISTRATORS],
                'canShareDocuments' => 1,
                'canExportDocuments' => 1,
                'canViewDocumentHistory' => 1
            ]);

        $seed->seed(EntityManager::C_ARCHIVE_FOLDERS)
            ->add([
                'folderId' => $folderIds['Default'],
                'title' => 'Default',
                'isSystem' => 1
            ]);

        $seed->seed(EntityManager::C_CUSTOM_METADATA)
            ->add([
                'metadataId' => $metadataIds['Invoices_SumCurrency'],
                'title' => InvoiceCustomMetadata::SUM_CURRENCY,
                'guiTitle' => InvoiceCustomMetadata::toString(InvoiceCustomMetadata::SUM_CURRENCY),
                'type' => CustomMetadataTypes::SYSTEM_INVOICE_SUM_CURRENCY,
                'isRequired' => 1
            ])
            ->add([
                'metadataId' => $metadataIds['Invoices_Sum'],
                'title' => InvoiceCustomMetadata::SUM,
                'guiTitle' => InvoiceCustomMetadata::toString(InvoiceCustomMetadata::SUM),
                'type' => CustomMetadataTypes::NUMBER,
                'isRequired' => 1
            ])
            ->add([
                'metadataId' => $metadataIds['Invoices_InvoiceNo'],
                'title' => InvoiceCustomMetadata::INVOICE_NO,
                'guiTitle' => InvoiceCustomMetadata::toString(InvoiceCustomMetadata::INVOICE_NO),
                'type' => CustomMetadataTypes::TEXT,
                'isRequired' => 1
            ])
            ->add([
                'metadataId' => $metadataIds['Invoices_Company'],
                'title' => InvoiceCustomMetadata::COMPANY,
                'guiTitle' => InvoiceCustomMetadata::toString(InvoiceCustomMetadata::COMPANY),
                'type' => CustomMetadataTypes::SYSTEM_INVOICE_COMPANIES,
                'isRequired' => 1
            ]);

        $metadataFolderRelationSeed = $seed->seed(EntityManager::C_CUSTOM_METADATA_FOLDER_RELATION);

        foreach($metadataIds as $title => $id) {
            $metadataFolderRelationSeed->add([
                'relationId' => $this->getId(EntityManager::C_CUSTOM_METADATA_FOLDER_RELATION),
                'customMetadataId' => $id,
                'folderId' => $folderIds['Invoices']
            ]);
        }

        $groupSeed = $seed->seed(EntityManager::C_GROUPS);

        foreach($groupIds as $value => $groupId) {
            $groupSeed->add([
                'groupId' => $groupId,
                'title' => $value
            ]);
        }

        $groupUserSeed = $seed->seed(EntityManager::C_GROUP_USERS_RELATION);

        foreach($users as $userId) {
            foreach($groupIds as $name => $groupId) {
                $groupUserSeed->add([
                    'relationId' => $this->getId(EntityManager::C_GROUP_USERS_RELATION),
                    'userId' => $userId,
                    'groupId' => $groupId
                ]);
            }
        }

        return $seed;
    }

    private function getContainerGroupUsers(): array {
        $sql = 'SELECT * FROM groups WHERE containerId IS NOT NULL';

        $result = $this->masterConn->query($sql);

        $groupId = null;
        if($result !== null) {
            foreach($result as $row) {
                $groupId = $row['groupId'];
            }
        }

        if($groupId === null) {
            throw new GeneralException('No group for container found.');
        }

        $sql = 'SELECT userId FROM group_users WHERE groupId = \'' . $groupId . '\'';

        $result = $this->masterConn->query($sql);

        $users = [];
        if($result !== null) {
            foreach($result as $row) {
                $users[] = $row['userId'];
            }
        }

        return $users;
    }
}

?>