<?php

namespace App\Constants;

class AuditLogObjectTypes extends AConstant {
    // 1 Superadministration
    public const SUPERADMINISTRATION = 1;

    // 1.1 Containers
    public const SA_CONTAINER = 11;
    public const SA_CONTAINER_DATABASE = 112;
    public const SA_CONTAINER_DATABASE_TABLE = 113;
    public const SA_CONTAINER_DATABASE_TABLE_COLUMN = 114;
    public const SA_CONTAINER_INVITE = 115;
    public const SA_CONTAINER_STATUS_HISTORY = 116;

    // 1.2 Groups
    public const SA_GROUP = 127;
    public const SA_GROUP_USER = 128;

    // 1.3 Users
    public const SA_USER = 139;
    public const SA_USER_ABSENCE = 1310;
    public const SA_USER_SUBSTITUTE = 1311;

    // 1.4 System services
    public const SA_SYSTEM_SERVICE = 1412;

    // 2 In-container
    public const INCONTAINER = 227;

    // 2.1 Archive
    public const C_ARCHIVE_FOLDER = 2113;
    public const C_ARCHIVE_FOLDER_DOCUMENT_RELATION = 2114;

    // 2.2 Documents
    public const C_DOCUMENT = 2215;
    public const C_DOCUMENT_CLASS = 2216;
    public const C_DOCUMENT_FOLDER = 2217;
    public const C_DOCUMENT_SHARING = 2218;

    // 2.3 External systems
    public const C_EXTERNAL_SYSTEM = 2319;
    public const C_EXTERNAL_SYSTEM_RIGHT = 2320;
    
    // 2.4 File storage
    public const C_FILE_STORAGE = 2421;

    // 2.5 Groups
    public const C_GROUP = 2522;
    public const C_GROUP_USER_RELATION = 2523;

    // 2.6 Processes
    public const C_PROCESS = 2624;
    public const C_PROCESS_COMMENT = 2625;
    public const C_PROCESS_TYPE = 2626;

    public const REQUEST = -1;
    public const INFORMATION = -2;

    public static function toString($key): ?string {
        return match((int)$key) {
            default => null,
            self::SA_CONTAINER => 'Container',
            self::SA_CONTAINER_DATABASE => 'Container database',
            self::SA_CONTAINER_DATABASE_TABLE => 'Container database table',
            self::SA_CONTAINER_DATABASE_TABLE_COLUMN => 'Container database table column',
            self::SA_CONTAINER_INVITE => 'Container invite',
            self::SA_CONTAINER_STATUS_HISTORY => 'Container status history',
            self::SA_GROUP => 'Group',
            self::SA_GROUP_USER => 'Group user',
            self::SA_USER => 'User',
            self::SA_USER_ABSENCE => 'User absence',
            self::SA_USER_SUBSTITUTE => 'User substitute',
            self::SA_SYSTEM_SERVICE => 'System service',

            self::C_ARCHIVE_FOLDER => 'Archive folder',
            self::C_ARCHIVE_FOLDER_DOCUMENT_RELATION => 'Archive folder document relation',
            self::C_DOCUMENT => 'Document',
            self::C_DOCUMENT_CLASS => 'Document class',
            self::C_DOCUMENT_FOLDER => 'Document folder',
            self::C_DOCUMENT_SHARING => 'Document sharing',
            self::C_EXTERNAL_SYSTEM => 'External system',
            self::C_EXTERNAL_SYSTEM_RIGHT => 'External system right',
            self::C_FILE_STORAGE => 'File storage',
            self::C_GROUP => 'Group',
            self::C_GROUP_USER_RELATION => 'Group user relation',
            self::C_PROCESS => 'Process',
            self::C_PROCESS_COMMENT => 'Process comment',
            self::C_PROCESS_TYPE => 'Process type',

            self::REQUEST => 'Request',
            self::INFORMATION => 'Information'
        };
    }
}

?>