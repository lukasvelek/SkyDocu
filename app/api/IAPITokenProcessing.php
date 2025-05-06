<?php

namespace App\Api;

/**
 * Common inteface for all auth/login API endpoints that compose an access token
 * 
 * @author Lukas Velek
 */
interface IAPITokenProcessing {
    /**
     * Composes a token with more information from raw token and returns Base-64 encoded string
     * 
     * @param string $token Raw token
     */
    function processToken(string $token): string;
}

?>