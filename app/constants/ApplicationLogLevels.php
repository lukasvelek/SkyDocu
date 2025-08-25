<?php

namespace App\Constants;

class ApplicationLogLevels extends AConstant {
    public const ERROR = 1;
    public const WARNING = 2;
    public const INFO = 3;
    public const SQL = 4;
    public const SERVICE = 5;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::ERROR => 'Error',
            self::WARNING => 'Warning',
            self::INFO => 'Information',
            self::SQL => 'SQL',
            self::SERVICE => 'Service',
            default => null
        };
    }

    public static function getConstByKey(string $key): ?string {
        return match($key) {
            'info' => self::INFO,
            'warning' => self::WARNING,
            'error' => self::ERROR,
            'sql' => self::SQL,
            'service' => self::SERVICE,
            default => null
        };
    }
}