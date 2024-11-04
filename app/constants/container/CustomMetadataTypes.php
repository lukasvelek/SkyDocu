<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class CustomMetadataTypes extends AConstant {
    public const ENUM = 1;
    public const TEXT = 2;
    public const NUMBER = 3;
    public const DATETIME = 4;
    public const BOOL = 5;

    public static function toString($key): string {
        return match((int)$key) {
            self::ENUM => 'Enum',
            self::TEXT => 'Text',
            self::NUMBER => 'Number',
            self::DATETIME => 'Datetime',
            self::BOOL => 'Boolean'
        };
    }

    public static function getAll(): array {
        return [
            self::ENUM => self::toString(self::ENUM),
            self::TEXT => self::toString(self::TEXT),
            self::NUMBER => self::toString(self::NUMBER),
            self::DATETIME => self::toString(self::DATETIME),
            self::BOOL => self::toString(self::BOOL)
        ];
    }
}

?>