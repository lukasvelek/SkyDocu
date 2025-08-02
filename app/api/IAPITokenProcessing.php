<?php

namespace App\Api;

use App\Entities\ExternalSystemTokenEntity;

/**
 * Common inteface for all auth/login API endpoints that compose an access token
 * 
 * @author Lukas Velek
 */
interface IAPITokenProcessing {
    /**
     * Composes a token with more information from raw token and returns Base-64 encoded string
     * 
     * @param ExternalSystemTokenEntity $token Token entity
     */
    function processToken(ExternalSystemTokenEntity $token): string;
}

?>