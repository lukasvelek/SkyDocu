<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class SystemProcessTypes extends AConstant {
    public const SHREDDING = 'shredding';

    public static function toString($key): string {
        return match($key) {
            self::SHREDDING => 'Shredding'
        };
    }
}

?>