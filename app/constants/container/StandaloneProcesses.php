<?php

namespace App\Constants\Container;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;

class StandaloneProcesses extends AConstant implements IColorable, IBackgroundColorable {
    public const HOME_OFFICE = 'homeOffice';
    public const FUNCTION_REQUEST = 'functionRequest';
    public const INVOICE = 'invoice';
    public const CONTAINER_REQUEST = 'containerRequest';

    public static function toString($key): ?string {
        return match($key) {
            self::HOME_OFFICE => 'Home office',
            self::FUNCTION_REQUEST => 'Function request',
            self::INVOICE => 'Invoice',
            self::CONTAINER_REQUEST => 'Container request',
            default => null
        };
    }

    public static function getDescription(string $key): ?string {
        return match($key) {
            default => null,
            self::HOME_OFFICE => 'Home office',
            self::FUNCTION_REQUEST => 'Request a function',
            self::INVOICE => 'Invoice',
            self::CONTAINER_REQUEST => 'Container request'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match($key) {
            default => null,
            self::HOME_OFFICE => 'rgb(125, 155, 255)',
            self::FUNCTION_REQUEST => 'rgb(175, 133, 180)',
            self::INVOICE => 'rgb(219, 188, 127)',
            self::CONTAINER_REQUEST => 'rgb(236, 137, 157)'
        };
    }

    public static function getColor($key): ?string {
        return match($key) {
            default => 'black',
            self::HOME_OFFICE => 'rgb(2, 32, 60)',
            self::FUNCTION_REQUEST => 'rgb(75, 33, 80)',
            self::INVOICE => 'rgb(92, 61, 0)',
            self::CONTAINER_REQUEST => 'rgb(99, 0, 20)'
        };
    }

    /**
     * Checks if given process is disabled
     * 
     * @param mixed $key
     */
    public static function isDisabled($key): bool {
        return match($key) {
            default => false,
            self::CONTAINER_REQUEST => true
        };
    }
}

?>
