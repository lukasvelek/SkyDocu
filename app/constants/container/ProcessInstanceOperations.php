<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ProcessInstanceOperations extends AConstant {
    public const PROCESS = 'process';
    public const CANCEL = 'cancel';
    public const FINISH = 'finish';
    public const ARCHIVE = 'archive';

    public static function toString($key): ?string {
        return match($key) {
            self::PROCESS => 'Process',
            self::CANCEL => 'Cancel',
            self::FINISH => 'Finish',
            self::ARCHIVE => 'Archive'
        };
    }
}

?>