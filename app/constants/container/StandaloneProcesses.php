<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class StandaloneProcesses extends AConstant {
    public const HOME_OFFICE = 'homeOffice';
    public const FUNCTION_REQUEST = 'functionRequest';

    public static function toString($key): ?string {
        return match($key) {
            self::HOME_OFFICE => 'Home office',
            self::FUNCTION_REQUEST => 'Function request',
            default => null
        };
    }

    public static function getDescription(string $key): ?string {
        return match($key) {
            default => null,
            self::HOME_OFFICE => 'Home office',
            self::FUNCTION_REQUEST => 'Request a function',
        };
    }

    public static function getForegroundColor(string $key): ?string {
        return match($key) {
            default => null,
            self::HOME_OFFICE => 'rgb(2, 32, 60)',
            self::FUNCTION_REQUEST => 'rgb(75, 33, 80)'
        };
    }

    public static function getBackgroundColor(string $key): ?string {
        return match($key) {
            default => null,
            self::HOME_OFFICE => 'rgb(125, 155, 255)',
            self::FUNCTION_REQUEST => 'rgb(175, 133, 180)'
        };
    }
}

?>
