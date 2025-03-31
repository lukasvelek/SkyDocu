<?php

namespace App\Constants;

class AuditLogActionTypes extends AConstant {
    public const CREATE = 1;
    public const READ = 2;
    public const UPDATE = 3;
    public const DELETE = 4;

    public static function toString($key): ?string {
        return match((int)$key) {
            default => null,
            self::CREATE => 'Create',
            self::READ => 'Read',
            self::UPDATE => 'Update',
            self::DELETE => 'Delete'
        };
    }
}

?>