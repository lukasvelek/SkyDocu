<?php

namespace App\Helpers;

/**
 * UnitConversionHelper contains useful methods for converting units
 * 
 * @author Lukas Velek
 */
class UnitConversionHelper {
    /**
     * Converts bytes to more user friendly form by converting to higher level units
     * 
     * E.g.: tries to convert bytes up to terabytes (or everything between). For example 1,250 bytes would be converted to 1,3 kB etc...
     * 
     * @param int $bytes Bytes
     * @return string Converted value with the unit
     */
    public static function convertBytesToUserFriendly(int $bytes): string {
        $kb = $mb = $gb = $tb = 0;

        while($bytes >= 1000) {
            $kb++;
            $bytes -= 1000;
        }

        while($kb >= 1000) {
            $mb++;
            $kb -= 1000;
        }

        while($mb >= 1000) {
            $gb++;
            $mb -= 1000;
        }

        while($gb >= 1000) {
            $tb++;
            $gb -= 1000;
        }

        if($gb == 0) {
            if($mb == 0) {
                if($kb == 0) {
                    return $bytes . ' B';
                }

                $tmp = (double)($kb) + (double)($bytes / 1_000);

                return round($tmp, 1) . ' kB';
            }

            $tmp = (double)($mb) + (double)($kb / 1_000) + (double)($bytes / 1_000_000);

            return round($tmp, 1) . ' MB';
        }

        $tmp = (double)($gb) + (double)($mb / 1_000) + (double)($kb / 1_000_000) + (double)($bytes / 1_000_000_000);

        return round($tmp, 1) . ' GB';
    }
}

?>