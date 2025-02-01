<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class SystemProcessTypes extends AConstant {
    public const SHREDDING = 'shredding';
    public const SHREDDING_REQUEST = 'shreddingRequest';
    public const MOVE_TO_ARCHIVE = 'moveToArchive';
    public const MOVE_FROM_ARCHIVE = 'moveFromArchive';

    public static function toString($key): ?string {
        return match($key) {
            self::SHREDDING => 'Shred',
            self::SHREDDING_REQUEST => 'Request shredding',
            self::MOVE_TO_ARCHIVE => 'Move to archive',
            self::MOVE_FROM_ARCHIVE => 'Move from archive',
            default => null
        };
    }

    public static function gridToString($key): ?string {
        return match($key) {
            self::SHREDDING => 'Shredding',
            self::SHREDDING_REQUEST => 'Shredding request',
            self::MOVE_FROM_ARCHIVE => 'Moving from archive',
            self::MOVE_TO_ARCHIVE => 'Moving to archive',
            default => null
        };
    }
}

?>