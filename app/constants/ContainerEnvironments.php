<?php

namespace App\Constants;

class ContainerEnvironments extends AConstant implements IColorable, IBackgroundColorable {
    //public const DEV = 1;
    public const TEST = 2;
    public const PROD = 3;

    public static function toString($key): string {
        return match((int)$key) {
            //self::DEV => 'Dev',
            self::TEST => 'Test',
            self::PROD => 'Prod'
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            default => null,
            self::TEST => 'blue',
            self::PROD => 'red',
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            default => null,
            self::TEST => 'lightblue',
            self::PROD => 'pink',
        };
    }
}

?>