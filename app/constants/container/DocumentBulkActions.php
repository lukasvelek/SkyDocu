<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class DocumentBulkActions extends AConstant {
    public const ARCHIVATION = 'archivation';
    public const DOCUMENT_HISTORY = 'documentHistory';
    public const SHARING = 'sharing';

    public static function toString($key): ?string {
        return match($key) {
            self::ARCHIVATION => 'Archive',
            self::DOCUMENT_HISTORY => 'History',
            self::SHARING => 'Share',
            default => null
        };
    }
}

?>