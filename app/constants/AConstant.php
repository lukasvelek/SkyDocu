<?php

namespace App\Constants;

use ReflectionClass;

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
    public static function getAll() {
        $rc = new ReflectionClass(static::class);
        $constants = $rc->getConstants();

        $result = [];
        foreach($constants as $name => $value) {
            $result[$value] = static::toString($value);
        }

        return $result;
    }
}

?>