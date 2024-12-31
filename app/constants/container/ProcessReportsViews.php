<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ProcessReportsViews extends AConstant {
    public const VIEW_ALL = 'all';
    public const VIEW_MY = 'my';

    public static function toString($key): ?string {
        return match($key) {
            self::VIEW_ALL => 'All',
            self::VIEW_MY => 'My'
        };
    }
}

?>