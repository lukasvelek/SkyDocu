<?php

namespace App\Constants\Container;

use App\Constants\AConstant;
use App\Constants\IBackgroundColorable;
use App\Constants\IColorable;

class ProcessInstanceStatus extends AConstant implements IColorable, IBackgroundColorable {
    public const NEW = 1;
    public const IN_PROGRESS = 2;
    public const CANCELED = 3;
    public const FINISHED = 4;
    public const ARCHIVED = 5;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::NEW => 'New',
            self::IN_PROGRESS => 'In progress',
            self::CANCELED => 'Canceled',
            self::FINISHED => 'Finished',
            self::ARCHIVED => 'Archived'
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'black',
            self::IN_PROGRESS => 'blue',
            self::CANCELED => 'red',
            self::FINISHED => 'green',
            self::ARCHIVED => 'purple',
            default => null
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'grey',
            self::IN_PROGRESS => 'lightblue',
            self::CANCELED => 'pink',
            self::FINISHED => 'lightgreen',
            self::ARCHIVED => 'pink',
            default => null
        };
    }
}

?>