<?php

namespace App\Constants;

class ContainerTiers extends AConstant {
    public const FREE = 1;
    public const STANDARD = 2;
    public const PREMIUM = 3;
    public const SERVICE = 4;

    public static function toString($key): ?string {
        return match((int) $key) {
            self::FREE => 'Free',
            self::STANDARD => 'Standard',
            self::PREMIUM => 'Premium',
            self::SERVICE => 'Service'
        };
    }
}