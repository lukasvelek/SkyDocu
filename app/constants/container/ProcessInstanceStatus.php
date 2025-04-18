<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ProcessInstanceStatus extends AConstant {
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
}

?>