<?php

namespace App\Constants;

class ProcessStatus extends AConstant implements IColorable, IBackgroundColorable {
    public const NEW = 1;
    public const IN_DISTRIBUTION = 2;
    public const NOT_IN_DISTRIBUTION = 3;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::NEW => 'New',
            self::IN_DISTRIBUTION => 'In distribution',
            self::NOT_IN_DISTRIBUTION => 'Not in distribution'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'lightblue',
            self::IN_DISTRIBUTION => 'lightgreen',
            self::NOT_IN_DISTRIBUTION => 'pink'
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'blue',
            self::IN_DISTRIBUTION => 'green',
            self::NOT_IN_DISTRIBUTION => 'red'
        };
    }
}

?>