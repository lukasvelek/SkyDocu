<?php

namespace App\Constants;

use App\Helpers\UnitConversionHelper;

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

    /**
     * Returns storage limit for tier or null if unlimited
     * 
     * @param int $tier Container tier
     */
    public static function getStorageLimitForTier(int $tier): ?int {
        return match($tier) {
            self::FREE => UnitConversionHelper::convertGigaBytesToBytes(1),
            self::STANDARD => UnitConversionHelper::convertGigaBytesToBytes(10),
            self::PREMIUM => null,
            self::SERVICE => null
        };
    }
}