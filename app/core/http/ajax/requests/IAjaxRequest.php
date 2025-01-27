<?php

namespace App\Core\Http\Ajax\Requests;

/**
 * Common interface for all AJAX requests
 * 
 * @author Lukas Velek
 */
interface IAjaxRequest {
    /**
     * Builds the JS code and returns it
     */
    function build(): string;
}

?>