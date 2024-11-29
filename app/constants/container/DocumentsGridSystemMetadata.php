<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class DocumentsGridSystemMetadata extends AConstant {
    public const TITLE = 'title';
    public const AUTHOR_USER_ID = 'authorUserId';
    public const STATUS = 'status';

    public static function toString($key): string {
        return match($key) {
            self::TITLE => 'Title',
            self::AUTHOR_USER_ID => 'Author',
            self::STATUS => 'Status'
        };
    }
}

?>