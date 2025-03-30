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

    public static function toString($key): ?string {
        return match((int)$key) {
            default => null,
            self::DOCUMENT => 'Document',
            self::PROCESS => 'Process',
            self::USER => 'User',
            self::EXTERNAL_SYSTEM => 'External system',
            self::PROCESS_TYPES => 'Process type',
            self::DOCUMENT_FOLDERS => 'Document folder',
            self::DOCUMENT_CLASSES => 'Document class'
        };
    }

    public static function getColor($key): ?string {
        switch((int)$key) {
            default:
                return 'black';

            case self::DOCUMENT:
            case self::DOCUMENT_FOLDERS:
            case self::DOCUMENT_CLASSES:
                return 'blue';

            case self::PROCESS:
            case self::PROCESS_TYPES:
                return 'red';

            case self::USER:
                return 'green';

            case self::EXTERNAL_SYSTEM:
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
                return 'lightblue';

            case self::PROCESS:
            case self::PROCESS_TYPES:
                return 'pink';

            case self::USER:
                return 'lightgreen';

            case self::EXTERNAL_SYSTEM:
                return 'pink';
        }
    }
}

?>