<?php

namespace App\Core\Http;

/**
 * Common class for responses
 * 
 * @author Lukas Velek
 */
abstract class AResponse {
    protected mixed $data;

    /**
     * Class constructor
     */
    public function __construct(mixed $data) {
        $this->data = $data;
    }

    /**
     * Returns the result of the response -> what can be displayed to the user
     * 
     * @return string
     */
    public abstract function getResult(): string;
}

?>