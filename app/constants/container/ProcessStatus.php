<?php

namespace App\Constants\Container;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;

class ProcessStatus extends AConstant implements IColorable, IBackgroundColorable {
    public const IN_PROGRESS = 1;
    public const FINISHED = 2;
    public const CANCELED = 3;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::IN_PROGRESS => 'In progress',
            self::FINISHED => 'Finished',
            self::CANCELED => 'Canceled',
            default => null
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::IN_PROGRESS => 'blue',
            self::FINISHED => 'green',
            self::CANCELED => 'red',
            default => 'black'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::IN_PROGRESS => 'lightblue',
            self::FINISHED => 'lightgreen',
            self::CANCELED => 'pink',
            default => null
        };
    }
}

?>