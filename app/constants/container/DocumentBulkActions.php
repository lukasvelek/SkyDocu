<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class DocumentBulkActions extends AConstant {
    public const DOCUMENT_HISTORY = 'documentHistory';
    public const SHARING = 'sharing';
    public const MOVE_TO_ARCHIVE = 'moveToArchive';
    public const MOVE_FROM_ARCHIVE = 'moveFromArchive';
    public const MOVE_TO_FOLDER = 'moveToFolder';

    public static function toString($key): ?string {
        return match($key) {
            self::DOCUMENT_HISTORY => 'History',
            self::SHARING => 'Share',
            self::MOVE_TO_ARCHIVE => 'Move to archive',
            self::MOVE_FROM_ARCHIVE => 'Move from archive',
            self::MOVE_TO_FOLDER => 'Move to folder',
            default => null
        };
    }
}

?>