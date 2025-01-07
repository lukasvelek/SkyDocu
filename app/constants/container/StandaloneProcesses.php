<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class StandaloneProcesses extends AConstant {
    public const HOME_OFFICE = 'homeOffice';

    public static function toString($key): ?string {
        return match($key) {
            self::HOME_OFFICE => 'Home office',
            default => null
        };
    }

    public static function getDescription(string $key): ?string {
        return match($key) {
            default => null,
            self::HOME_OFFICE => 'Home office'
        };
    }

    public static function getForegroundColor(string $key): ?string {
        return match($key) {
            default => null,
            self::HOME_OFFICE => 'rgb(2, 32, 60)'
        };
    }

    public static function getBackgroundColor(string $key): ?string {
        return match($key) {
            default => null,
            self::HOME_OFFICE => 'rgb(125, 155, 283)'
        };
    }
}

?>
