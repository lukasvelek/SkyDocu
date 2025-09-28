<?php

namespace App\Constants\Container;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;

class ReportStatus extends AConstant implements IColorable, IBackgroundColorable {
    public const NEW = 1;
    public const PUBLISHED = 2;
    public const REMOVED = 3;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::NEW => 'New',
            self::PUBLISHED => 'Published',
            self::REMOVED => 'Removed'
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'blue',
            self::PUBLISHED => 'green',
            self::REMOVED => 'red'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'lightblue',
            self::PUBLISHED => 'lightgreen',
            self::REMOVED => 'pink'
        };
    }
}