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

    public static function getDescription($key): ?string {
        return match($key) {
            default => null,
            self::HOME_OFFICE => 'Home office'
        };
    }
}

?>
