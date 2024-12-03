<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ProcessGridViews extends AConstant {
    public const VIEW_ALL = 'all';
    public const VIEW_STARTED_BY_ME = 'startedByMe';
    public const VIEW_WITH_ME = 'withMe';
    public const VIEW_WAITING_FOR_ME = 'waitingForMe';

    public static function toString($key): string {
        return match($key) {
            self::VIEW_ALL => 'All',
            self::VIEW_STARTED_BY_ME => 'Started by me',
            self::VIEW_WITH_ME => 'With me',
            self::VIEW_WAITING_FOR_ME => 'Waiting for me'
        };
    }
}

?>