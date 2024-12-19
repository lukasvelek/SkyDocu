<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class SystemGroups extends AConstant {
    public const ALL_USERS = 'allUsers';
    public const ADMINISTRATORS = 'administrators';
    public const ARCHIVISTS = 'archivists';

    public static function toString($key): ?string {
        return match($key) {
            self::ALL_USERS => 'All users',
            self::ADMINISTRATORS => 'Administrators',
            self::ARCHIVISTS => 'Archivists',
            default => null
        };
    }
}

?>