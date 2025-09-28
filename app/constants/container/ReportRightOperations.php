<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ReportRightOperations extends AConstant {
    public const READ = 'read';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const GRANT = 'grant';

    public static function toString($key): ?string {
        return match($key) {
            self::READ => 'Read',
            self::EDIT => 'Edit',
            self::DELETE => 'Delete',
            self::GRANT => 'Grant'
        };
    }
}