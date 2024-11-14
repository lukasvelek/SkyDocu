<?php

namespace App\Constants;

/**
 * Common class for constants that can be displayed in the UI because they have all the methods needed
 * 
 * @author Lukas Velek
 */
abstract class AConstant implements IToStringConstant {
    /**
     * Returns all the values in the constant. The array is formatted like:
     * constant key => result of toString()
     * 
     * @return array All constant values
     */
    abstract static function getAll(): array;
}

?>