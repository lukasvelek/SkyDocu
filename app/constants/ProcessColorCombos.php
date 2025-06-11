<?php

namespace App\Constants;

class ProcessColorCombos extends AConstant implements IColorable, IBackgroundColorable {
    public const BLUE = 'blue';
    public const RED = 'red';
    public const GREEN = 'green';
    public const GREY = 'grey';
    public const PURPLE = 'purple';

    public static function toString($key): ?string {
        return match($key) {
            default => null,
            self::BLUE => 'Blue',
            self::RED => 'Red',
            self::GREEN => 'Green',
            self::GREY => 'Grey',
            self::PURPLE => 'Purple'
        };
    }

    public static function getColor($key): ?string {
        return match($key) {
            default => null,
            self::BLUE => 'blue',
            self::RED => 'red',
            self::GREEN => 'green',
            self::GREY => 'black',
            self::PURPLE => 'purple'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match($key) {
            default => null,
            self::BLUE => 'lightblue',
            self::RED => 'pink',
            self::GREEN => 'lightgreen',
            self::GREY => 'grey',
            self::PURPLE => 'pink'
        };
    }
}

?>