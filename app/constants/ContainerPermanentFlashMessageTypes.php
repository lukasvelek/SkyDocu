<?php

namespace App\Constants;

class ContainerPermanentFlashMessageTypes extends AConstant {
    public const INFO = 1;
    public const SUCCESS = 2;
    public const WARNING = 3;
    public const ERROR = 4;
    public const PERMANENT = 5;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::INFO => 'Info',
            self::SUCCESS => 'Success',
            self::WARNING => 'Warning',
            self::ERROR => 'Error',
            self::PERMANENT => 'Permanent'
        };
    }

    public static function getKey(int $key): string {
        return match((int)$key) {
            self::INFO => 'info',
            self::ERROR => 'error',
            self::WARNING => 'warning',
            self::SUCCESS => 'success',
            self::PERMANENT => 'permanent'
        };
    }
}