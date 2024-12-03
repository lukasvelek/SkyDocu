<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ProcessStatus extends AConstant {
    public const NEW = 1;
    public const IN_PROGRESS = 2;
    public const FINISHED = 3;

    public static function toString($key): string {
        return match((int)$key) {
            self::NEW => 'New',
            self::IN_PROGRESS => 'In progress',
            self::FINISHED => 'Finished'
        };
    }
}

?>