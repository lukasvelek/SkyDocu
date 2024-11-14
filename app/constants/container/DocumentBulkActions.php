<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class DocumentBulkActions extends AConstant {
    public const ARCHIVATION = 'archivation';
    public const DOCUMENT_HISTORY = 'documentHistory';

    public static function toString($key): string {
        return match($key) {
            self::ARCHIVATION => 'Archive',
            self::DOCUMENT_HISTORY => 'History'
        };
    }

    public static function getAll(): array {
        return [
            self::ARCHIVATION => self::toString(self::ARCHIVATION),
            self::DOCUMENT_HISTORY => self::toString(self::DOCUMENT_HISTORY),
        ];
    }
}

?>