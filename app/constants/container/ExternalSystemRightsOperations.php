<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ExternalSystemRightsOperations extends AConstant {
    public const READ_DOCUMENTS = 'readDocuments';
    public const READ_DOCUMENT_FOLDERS = 'readDocumentFolders';
    public const READ_USERS = 'readUsers';
    public const READ_PROCESSES = 'readProcesses';
    public const READ_PROCESS_TYPES = 'readProcessTypes';
    public const CREATE_DOCUMENTS = 'createDocuments';
    public const READ_DOCUMENT_CLASSES = 'readDocumentClasses';
    public const READ_DOCUMENT_SHARINGS = 'readDocumentSharings';
    public const READ_FILES = 'readFiles';
    public const READ_ARCHIVE_FOLDERS = 'readArchiveFolders';
    public const READ_TRANSACTION_LOG = 'readTransactionLog';
    public const UPLOAD_FILES = 'uploadFiles';

    public static function toString($key): ?string {
        return match($key) {
            self::READ_DOCUMENTS => 'Read documents',
            self::READ_DOCUMENT_FOLDERS => 'Read document folders',
            self::READ_USERS => 'Read users',
            self::READ_PROCESSES => 'Read processes',
            self::READ_PROCESS_TYPES => 'Read process types',
            self::CREATE_DOCUMENTS => 'Create documents',
            self::READ_DOCUMENT_CLASSES => 'Read document classes',
            self::READ_DOCUMENT_SHARINGS => 'Read document sharings',
            self::READ_FILES => 'Read files',
            self::READ_ARCHIVE_FOLDERS => 'Read archive folders',
            self::READ_TRANSACTION_LOG => 'Read transaction log',
            self::UPLOAD_FILES => 'Upload files'
        };
    }
}

?>