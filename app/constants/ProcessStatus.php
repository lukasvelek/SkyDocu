<?php

namespace App\Constants;

class ProcessStatus extends AConstant {
    public const NEW = 1;
    public const IN_DISTRIBUTION = 2;
    public const NEWER_THAN_IN_DISTRIBUTION = 3;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::NEW => 'New',
            self::IN_DISTRIBUTION => 'In distribution',
            self::NEWER_THAN_IN_DISTRIBUTION => 'Newer than in distribution'
        };
    }
}

?>