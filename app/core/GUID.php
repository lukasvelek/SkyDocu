<?php

namespace App\Core;

use App\Exceptions\GeneralException;

/**
 * Class for GUID operations
 * 
 * @author Lukas Velek
 */
class GUID {
    /**
     * Generates a GUID
     * 
     * @throws GeneralException
     */
    public static function generate(): string {
        // Windows
        if(function_exists('com_create_guid')) {
            $guid = com_create_guid();

            return trim($guid, '{}');
        }

        // OSX/Linux
        if(function_exists('openssl_random_pseudo_bytes')) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        throw new GeneralException('No generation methods are available.');
    }
}