<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class DocumentStatus extends AConstant {
    public const NEW = 1;
    public const SHREDDED = 2;
    public const ARCHIVED = 3;
    public const DELETED = 4;

    public static function toString($key): string {
        return match((int)$key) {
            self::NEW => 'New',
            self::SHREDDED => 'Shredded',
            self::ARCHIVED => 'Archived',
            self::DELETED => 'Deleted'
        };
    }
}

?>