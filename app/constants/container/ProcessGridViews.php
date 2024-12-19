<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ProcessGridViews extends AConstant {
    public const VIEW_ALL = 'all';
    public const VIEW_STARTED_BY_ME = 'startedByMe';
    public const VIEW_WITH_ME = 'withMe';
    public const VIEW_WAITING_FOR_ME = 'waitingForMe';
    public const VIEW_FINISHED = 'finished';

    public static function toString($key): ?string {
        return match($key) {
            self::VIEW_ALL => 'All processes',
            self::VIEW_STARTED_BY_ME => 'Processes started by me',
            self::VIEW_WITH_ME => 'Processes with me',
            self::VIEW_WAITING_FOR_ME => 'Processes waiting for me',
            self::VIEW_FINISHED => 'Finished',
            default => null
        };
    }
}

?>