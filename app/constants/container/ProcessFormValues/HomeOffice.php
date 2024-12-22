<?php

namespace App\Constants\Container\ProcessFormValues;

use App\Constants\AConstant;

class HomeOffice extends AConstant {
    public const REASON = 'reason';
    public const DATE_FROM = 'dateFrom';
    public const DATE_TO = 'dateTo';

    public static function toString($key): ?string {
        return match($key) {
            self::REASON => 'Reason',
            self::DATE_FROM => 'Date from',
            self::DATE_TO => 'Date to'
        };
    }
}

?>