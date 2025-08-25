<?php

namespace App\Constants;

class ApplicationLogTypes extends AConstant {
    public const APPLICATION = 1;
    public const SQL = 2;
    public const SERVICE = 3;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::APPLICATION => 'Application',
            self::SQL => 'SQL',
            self::SERVICE => 'Service',
            default => null
        };
    }
}