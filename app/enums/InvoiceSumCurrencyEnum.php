<?php

namespace App\Enums;

use App\Constants\Container\InvoiceCurrencies;
use App\Core\Datatypes\ArrayList;

/**
 * Metadata invoice sum currency enum
 * 
 * @author Lukas Velek
 */
class InvoiceSumCurrencyEnum extends AEnumForMetadata {
    public function getAll(): ArrayList {
        if($this->cache->isEmpty()) {
            $this->cache->add('null', [self::KEY => 'null', self::TITLE => '-']);

            foreach(InvoiceCurrencies::getAll() as $key => $title) {
                $this->cache->add($key, [self::KEY => $key, self::TITLE => $title]);
            }
        }

        return $this->cache;
    }
}

?>