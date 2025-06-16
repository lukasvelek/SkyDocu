<?php

namespace App\Constants;

class JobQueueProcessingHistoryTypes extends AConstant {
    public const GENERAL_MESSAGE = 1;
    public const JOB_PROCESSING_STARTED = 2;
    public const JOB_PROCESSING_ENDED = 3;
    public const DEBUG_MESSAGE = 4;
    public const ERROR_MESSAGE = 5;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::GENERAL_MESSAGE => 'General message',
            self::JOB_PROCESSING_STARTED => 'Job processing started',
            self::JOB_PROCESSING_ENDED => 'Job processing ended',
            self::DEBUG_MESSAGE => 'Debug message',
            self::ERROR_MESSAGE => 'Error message'
        };
    }
}

?>