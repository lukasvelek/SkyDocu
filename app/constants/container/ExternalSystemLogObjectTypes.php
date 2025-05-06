<?php

namespace App\Constants\Container;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;

/**
 * External system object types for log
 * 
 * @author Lukas Velek
 */
class ExternalSystemLogObjectTypes extends AConstant implements IColorable, IBackgroundColorable {
    public const DOCUMENT = 1;
    public const PROCESS = 2;
    public const USER = 3;
    public const EXTERNAL_SYSTEM = 4;
    public const PROCESS_TYPES = 5;
    public const DOCUMENT_FOLDERS = 6;
    public const DOCUMENT_CLASSES = 7;
    public const DOCUMENT_SHARING = 8;
    public const FILES = 9;
    public const ARCHIVE_FOLDERS = 10;
    public const TRANSACTION_LOG = 11;
    public const PEEQL = 12;
    public const PROCESS_INSTANCES = 13;

    public static function toString($key): ?string {
        return match((int)$key) {
            default => null,
            self::DOCUMENT => 'Document',
            self::PROCESS => 'Process',
            self::PROCESS_INSTANCES => 'Process instances',
            self::USER => 'User',
            self::EXTERNAL_SYSTEM => 'External system',
            self::PROCESS_TYPES => 'Process type',
            self::DOCUMENT_FOLDERS => 'Document folder',
            self::DOCUMENT_CLASSES => 'Document class',
            self::DOCUMENT_SHARING => 'Document sharing',
            self::FILES => 'File',
            self::ARCHIVE_FOLDERS => 'Archive folder',
            self::TRANSACTION_LOG => 'Transaction log',
            self::PEEQL => 'PeeQL'
        };
    }

    public static function getColor($key): ?string {
        switch((int)$key) {
            default:
                return 'black';

            case self::DOCUMENT:
            case self::DOCUMENT_FOLDERS:
            case self::DOCUMENT_CLASSES:
            case self::DOCUMENT_SHARING:
            case self::ARCHIVE_FOLDERS:
                return 'blue';

            case self::PROCESS:
            case self::PROCESS_TYPES:
            case self::PROCESS_INSTANCES:
                return 'red';

            case self::USER:
            case self::PEEQL:
                return 'green';

            case self::EXTERNAL_SYSTEM:
            case self::FILES:
            case self::TRANSACTION_LOG:
                return 'purple';
        }
    }

    public static function getBackgroundColor($key): ?string {
        switch((int)$key) {
            default:
                return null;

            case self::DOCUMENT:
            case self::DOCUMENT_FOLDERS:
            case self::DOCUMENT_CLASSES:
            case self::DOCUMENT_SHARING:
            case self::ARCHIVE_FOLDERS:
                return 'lightblue';

            case self::PROCESS:
            case self::PROCESS_TYPES:
            case self::PROCESS_INSTANCES:
                return 'pink';

            case self::USER:
            case self::PEEQL:
                return 'lightgreen';

            case self::EXTERNAL_SYSTEM:
            case self::FILES:
            case self::TRANSACTION_LOG:
                return 'pink';
        }
    }
}

?>