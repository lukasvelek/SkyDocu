<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class GridNames extends AConstant {
    public const DOCUMENTS_GRID = 'documentsGrid';

    public static function toString($key): string {
        return match($key) {
            self::DOCUMENTS_GRID => 'Documents grid'
        };
    }
}

?>