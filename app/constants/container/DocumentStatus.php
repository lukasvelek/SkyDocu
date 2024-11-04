<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class DocumentStatus extends AConstant {
    public const NEW = 1;
    public const SHREDDED = 2;
    public const ARCHIVED = 3;
    public const DELETED = 4;

    public static function getAll(): array {
        return [
            self::NEW => self::toString(self::NEW),
            self::ARCHIVED => self::toString(self::ARCHIVED),
            self::SHREDDED => self::toString(self::SHREDDED),
            self::DELETED => self::toString(self::DELETED)
        ];   
    }

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