<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class CustomMetadataTypes extends AConstant {
    public const ENUM = 1;
    public const TEXT = 2;
    public const NUMBER = 3;
    public const DATETIME = 4;
    public const BOOL = 5;
    public const DATE = 6;

    // Numbers bigger than 100 are system enums
    public const SYSTEM_USER = 100;
    public const SYSTEM_INVOICE_SUM_CURRENCY = 101;
    public const SYSTEM_INVOICE_COMPANIES = 102;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::ENUM => 'Enum',
            self::TEXT => 'Text',
            self::NUMBER => 'Number',
            self::DATETIME => 'Datetime',
            self::BOOL => 'Boolean',
            self::DATE => 'Date',
            self::SYSTEM_USER => 'User',
            self::SYSTEM_INVOICE_SUM_CURRENCY => 'Invoice - Sum currency',
            self::SYSTEM_INVOICE_COMPANIES => 'Invoice - Companies',
            default => null
        };
    }
}

?>