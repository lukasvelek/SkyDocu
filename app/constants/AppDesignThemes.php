<?php

namespace App\Constants;

/**
 * AppDesignThemes defines different app design themes
 * 
 * @author Luaks Velek
 */
class AppDesignThemes extends AConstant {
    public const LIGHT = 0;
    public const DARK = 1;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::LIGHT => 'Light',
            self::DARK => 'Dark',
            default => null
        };
    }

    /**
     * Convert app design theme to app style file name
     * 
     * @param mixed $key
     */
    public static function convertToStyleFileName($key): string {
        return match((int)$key) {
            self::LIGHT => 'style.light.css',
            self::DARK => 'style.dark.css',
            default => 'style.light.css'
        };
    }
}

?>