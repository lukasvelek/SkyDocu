<?php

namespace App\Constants;

class ApplicationLogTypes extends AConstant implements IColorable, IBackgroundColorable {
    public const INFO = 'info';
    public const WARNING = 'warning';
    public const ERROR = 'error';
    public const STOPWATCH = 'stopwatch';
    public const CACHE = 'cache';

    public static function toString($key): ?string {
        return match($key) {
            self::INFO => 'Information',
            self::WARNING => 'Warning',
            self::ERROR => 'Error',
            self::STOPWATCH => 'Stopwatch',
            self::CACHE => 'Cache',
            default => null
        };
    }

    public static function getColor($key): ?string {
        return match($key) {
            self::INFO => 'blue',
            self::WARNING => 'orange',
            self::ERROR => 'red',
            self::STOPWATCH => 'black',
            self::CACHE => 'black',
            default => null
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match($key) {
            self::INFO => 'lightblue',
            self::WARNING => 'yellow',
            self::ERROR => 'pink',
            self::STOPWATCH => 'grey',
            self::CACHE => 'grey',
            default => null
        };
    }
}