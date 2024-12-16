<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ProcessStatus extends AConstant {
    public const IN_PROGRESS = 1;
    public const FINISHED = 2;
    public const CANCELED = 3;

    public static function toString($key): string {
        return match((int)$key) {
            self::IN_PROGRESS => 'In progress',
            self::FINISHED => 'Finished',
            self::CANCELED => 'Canceled'
        };
    }
}

?>