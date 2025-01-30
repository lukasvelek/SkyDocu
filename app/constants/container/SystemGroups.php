<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class SystemGroups extends AConstant {
    public const ALL_USERS = 'allUsers';
    public const ADMINISTRATORS = 'administrators';
    public const ARCHIVISTS = 'archivists';
    public const PROCESS_SUPERVISOR = 'processSupervisor';

    public static function toString($key): ?string {
        return match($key) {
            self::ALL_USERS => 'All users',
            self::ADMINISTRATORS => 'Administrators',
            self::ARCHIVISTS => 'Archivists',
            self::PROCESS_SUPERVISOR => 'Process supervisor',
            default => null
        };
    }
}

?>