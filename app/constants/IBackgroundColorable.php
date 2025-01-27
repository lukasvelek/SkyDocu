<?php

namespace App\Constants;

/**
 * Interface for constants with background colorable values - e.g. in grid
 * 
 * @author Lukas Velek
 */
interface IBackgroundColorable {
    /**
     * Returns background color of the constant
     * 
     * @param mixed $key Constant key
     * @return string Background color
     */
    static function getBackgroundColor($key): ?string;
}

?>