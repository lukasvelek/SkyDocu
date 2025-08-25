<?php

namespace App\Constants;

class ApplicationLogLevels extends AConstant implements IColorable, IBackgroundColorable {
    public const ERROR = 1;
    public const WARNING = 2;
    public const INFO = 3;
    public const SQL = 4;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::ERROR => 'Error',
            self::WARNING => 'Warning',
            self::INFO => 'Information',
            self::SQL => 'SQL',
            default => null
        };
    }

    public static function getConstByKey(string $key): ?string {
        return match($key) {
            'info' => self::INFO,
            'warning' => self::WARNING,
            'error' => self::ERROR,
            'sql' => self::SQL,
            default => null
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::ERROR => 'red',
            self::WARNING => 'yellow',
            self::INFO => 'green',
            self::SQL => 'blue'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::ERROR => 'pink',
            self::WARNING => 'orange',
            self::INFO => 'lightgreen',
            self::SQL => 'lightblue'
        };
    }
}