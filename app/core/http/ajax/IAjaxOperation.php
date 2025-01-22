<?php

namespace App\Core\Http\Ajax;

/**
 * Common interface for all operations used in ajax requests
 * 
 * @author Lukas Velek
 */
interface IAjaxOperation {
    /**
     * Builds the code to a single string
     */
    function build(): string;
}

?>