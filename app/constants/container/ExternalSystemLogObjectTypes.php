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

    public static function toString($key): ?string {
        return match((int)$key) {
            default => null,
            self::DOCUMENT => 'Document',
            self::PROCESS => 'Process',
            self::USER => 'User',
            self::EXTERNAL_SYSTEM => 'External system',
            self::PROCESS_TYPES => 'Process types'
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            default => 'black',
            self::DOCUMENT => 'blue',
            self::PROCESS => 'red',
            self::PROCESS_TYPES => 'red',
            self::USER => 'green',
            self::EXTERNAL_SYSTEM => 'purple'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            default => null,
            self::DOCUMENT => 'lightblue',
            self::PROCESS => 'pink',
            self::PROCESS_TYPES => 'pink',
            self::USER => 'lightgreen',
            self::EXTERNAL_SYSTEM => 'pink'
        };
    }
}

?>