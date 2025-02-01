<?php

namespace App\Constants\Container;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;

class ArchiveFolderStatus extends AConstant implements IColorable, IBackgroundColorable {
    public const NEW = 1;
    public const ARCHIVED = 2;
    public const SHREDDED = 3;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::NEW => 'New',
            self::ARCHIVED => 'Archived',
            self::SHREDDED => 'Shredded',
            default => null
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'blue',
            self::ARCHIVED => 'green',
            self::SHREDDED => 'black',
            default => 'black'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'lightblue',
            self::ARCHIVED => 'lightgreen',
            self::SHREDDED => 'lightgrey',
            default => null
        };
    }
}

?>