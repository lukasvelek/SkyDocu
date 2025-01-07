<?php

namespace App\Constants;

/**
 * Interface for constants with colorable values - e.g. in grid
 * 
 * @author Lukas Velek
 */
interface IColorable {
    /**
     * Returns text color of the constant
     * 
     * @param mixed $key Constant key
     * @return string Text color
     */
    static function getColor($key): ?string;
}

?>