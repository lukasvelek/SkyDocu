<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

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

        $table->create('processes')
            ->primaryKey('processId')
            ->varchar('documentId', 256, true)
            ->varchar('type')
            ->varchar('authorUserId')
            ->varchar('currentOfficerUserId', 256, true)
            ->varchar('workflowUserIds', 256, true)
            ->datetimeAuto('dateCreated')
            ->integer('status', 4)
            ->default('status', 1)
            ->varchar('currentOfficerSubstituteUserId', 256, true)
            ->index(['currentOfficerUserId', 'authorUserId'])
            ->index(['documentId']);

        $table->create('process_types')
            ->primaryKey('typeId')
            ->varchar('typeKey')
            ->varchar('title')
            ->text('description')
            ->bool('isEnabled')
            ->default('isEnabled', 1);

        $table->create('process_comments')
            ->primaryKey('commentId')
            ->varchar('processId')
            ->varchar('userId')
            ->text('description')
            ->datetimeAuto('dateCreated')
            ->index(['processId']);

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

        $table->create('process_metadata_history')
            ->primaryKey('entryId')
            ->varchar('processId')
            ->varchar('userId')
            ->varchar('metadataName')
            ->varchar('oldValue', 256, true)
            ->varchar('newValue', 256, true)
            ->datetimeAuto('dateCreated')
            ->index(['processId']);

        $table->create('process_data')
            ->primaryKey('entryId')
            ->varchar('processId')
            ->text('data')
            ->index(['processId']);

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
        
    }
}

?>