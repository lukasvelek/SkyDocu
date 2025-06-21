<?php

namespace App\Constants\Container;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;

/**
 * External system action types for log
 * 
 * @author Lukas Velek
 */
class ExternalSystemLogActionTypes extends AConstant implements IColorable, IBackgroundColorable {
    public const CREATE = 1;
    public const READ = 2;
    public const UPDATE = 3;
    public const DELETE = 4;
    public const LOGIN = 5;
    public const PEEQL = 6;

    public static function toString($key): ?string {
        return match((int)$key) {
            default => null,
            self::CREATE => 'Create',
            self::READ => 'Read',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete',
            self::LOGIN => 'Login',
            self::PEEQL => 'PeeQL'
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            default => 'black',
            self::CREATE => 'blue',
            self::READ => 'green',
            self::UPDATE => 'orange',
            self::DELETE => 'red',
            self::LOGIN => 'purple',
            self::PEEQL => 'orange'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            default => null,
            self::CREATE => 'lightblue',
            self::READ => 'lightgreen',
            self::UPDATE => 'yellow',
            self::DELETE => 'pink',
            self::LOGIN => 'pink',
            self::PEEQL => 'yellow'
        };
    }
}

?>