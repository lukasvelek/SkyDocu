<?php

namespace App\Constants\Container;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;

class ProcessStatus extends AConstant implements IColorable, IBackgroundColorable {
    public const IN_DISTRIBUTION = 1;
    public const NOT_IN_DISTRIBUTION = 2;
    public const CUSTOM = 3;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::IN_DISTRIBUTION => 'In distribution',
            self::NOT_IN_DISTRIBUTION => 'Not in distribution',
            self::CUSTOM => 'Custom',
            default => null
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::IN_DISTRIBUTION => 'green',
            self::NOT_IN_DISTRIBUTION => 'red',
            self::CUSTOM => 'blue',
            default => 'black'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::IN_DISTRIBUTION => 'lightgreen',
            self::NOT_IN_DISTRIBUTION => 'pink',
            self::CUSTOM => 'lightblue',
            default => null
        };
    }
}

?>