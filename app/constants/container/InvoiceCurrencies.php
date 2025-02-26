<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class InvoiceCurrencies extends AConstant {
    public const USD = 'usd';
    public const EUR = 'eur';
    public const GBP = 'gbp';
    public const CZK = 'czk';
    public const JPY = 'jpy';

    public static function toString($key): ?string {
        $constants = self::getAllConstants();

        if(!in_array($key, $constants)) {
            return null;
        }

        return array_search($key, $constants);
    }
}

?>