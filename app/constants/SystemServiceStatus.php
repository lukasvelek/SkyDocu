<?php

namespace App\Constants;

class SystemServiceStatus extends AConstant implements IColorable, IBackgroundColorable {
    public const NOT_RUNNING = 1;
    public const RUNNING = 2;

    public static function toString($key): string {
        return match((int)$key) {
            self::NOT_RUNNING => 'Not running',
            self::RUNNING => 'Running'
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::NOT_RUNNING => 'red',
            self::RUNNING => 'green',
            default => 'black'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::NOT_RUNNING => 'pink',
            self::RUNNING => 'lime',
            default => null
        };
    }
}

?>