<?php

namespace PeeQL\Result;

/**
 * Common class for all results
 * 
 * @author Lukas Velek
 */
abstract class AResult {
    protected bool $error;
    protected string $errorMessage;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->error = false;
    }

    /**
     * Sets error
     */
    public function setError(string $message) {
        $this->errorMessage = $message;
        $this->error = true;
    }

    /**
     * Processes the result and returns an array with all the information that is returned to the user
     */
    protected abstract function processResult(): array;

    /**
     * Returns the result of the operation
     */
    public function getResult(): string {
        return json_encode($this->processResult());
    }
}

?>