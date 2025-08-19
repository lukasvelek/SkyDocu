<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ProcessInstanceOperations extends AConstant {
    public const CANCEL = 'cancel';
    public const FINISH = 'finish';
    public const ARCHIVE = 'archive';
    public const ACCEPT = 'accept';
    public const REJECT = 'reject';
    public const CREATE = 'create';
    public const PROCESS = 'process';
    public const SYS_REASSIGN = 'sys_reassign';

    public static function toString($key): ?string {
        return match($key) {
            self::CANCEL => 'Cancel',
            self::FINISH => 'Finish',
            self::ARCHIVE => 'Archive',
            self::ACCEPT => 'Accept',
            self::REJECT => 'Reject',
            self::CREATE => 'Create',
            self::PROCESS => 'Process',
            self::SYS_REASSIGN => 'Reassigned by system'
        };
    }
}

?>