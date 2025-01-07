<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class SystemProcessTypes extends AConstant {
    public const SHREDDING = 'shredding';
    public const SHREDDING_REQUEST = 'shreddingRequest';
    public const ARCHIVATION = 'archivation';

    public static function toString($key): ?string {
        return match($key) {
            self::SHREDDING => 'Shred',
            self::SHREDDING_REQUEST => 'Request shredding',
            self::ARCHIVATION => 'Archive',
            default => null
        };
    }

    public static function gridToString($key): ?string {
        return match($key) {
            self::SHREDDING => 'Shredding',
            self::SHREDDING_REQUEST => 'Shredding request',
            self::ARCHIVATION => 'Archivation',
            default => null
        };
    }
}

?>