<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class DocumentsGridSystemMetadata extends AConstant {
    public const TITLE = 'title';
    public const AUTHOR_USER_ID = 'authorUserId';
    public const STATUS = 'status';
    public const IS_IN_PROCESS = 'isInProcess';
    public const HAS_FILE = 'hasFile';

    public static function toString($key): ?string {
        return match($key) {
            self::TITLE => 'Title',
            self::AUTHOR_USER_ID => 'Author',
            self::STATUS => 'Status',
            self::IS_IN_PROCESS => 'In process',
            self::HAS_FILE => 'Has file',
            default => null
        };
    }
}

?>