<?php

namespace App\Constants;

/**
 * Interface for constants that should have toString() function that will return
 * the user-friendly version of the constant.
 * 
 * @author Lukas Velek
 */
interface IToStringConstant {
    /**
     * Returns user-friendly version of the constant
     * 
     * @param mixed $key Constant key
     * @return string User-friendly text
     */
    static function toString($key): string;
}

?>