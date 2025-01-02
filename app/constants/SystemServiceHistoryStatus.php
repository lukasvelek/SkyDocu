<?php

namespace App\Constants;

class SystemServiceHistoryStatus extends AConstant implements IColorable {
    public const SUCCESS = 1;
    public const ERROR = 2;

    public static function toString($key): string {
        return match((int)$key) {
            self::SUCCESS => 'Success',
            self::ERROR => 'Error'
        };
    }

    public static function getColor($key): ?string
    {
        return match((int)$key) {
            self::SUCCESS => 'green',
            self::ERROR => 'red',
            default => 'black'
        };
    }
}

?>