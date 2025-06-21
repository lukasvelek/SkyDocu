<?php

namespace App\Constants;

class JobQueueStatus extends AConstant implements IColorable, IBackgroundColorable {
    public const NEW = 1;
    public const IN_PROGRESS = 2;
    public const ERROR = 3;
    public const FINISHED = 4;
    
    public static function toString($key): ?string {
        return match((int)$key) {
            self::NEW => 'New',
            self::IN_PROGRESS => 'In progress',
            self::ERROR => 'Error',
            self::FINISHED => 'Finished'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'grey',
            self::IN_PROGRESS => 'lightblue',
            self::ERROR => 'pink',
            self::FINISHED => 'lightgreen'
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'black',
            self::IN_PROGRESS => 'blue',
            self::ERROR => 'red',
            self::FINISHED => 'green'
        };
    }
}

?>