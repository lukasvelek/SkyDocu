<?php

namespace App\Api;

/**
 * Common class for Create*Controllers
 * 
 * @author Lukas Velek
 */
abstract class ACreateAPIOperation extends AAuthenticatedApiController {
    protected array $properties;
    protected array $params = [];

    /**
     * Sets allowed properties
     * 
     * @param array $properties Properties
     */
    protected function setProperties(array $properties) {
        $this->properties = $properties;

        $this->processProperties();
    }

    /**
     * Checks entered properties and eliminates disallowed ones
     */
    private function processProperties() {
        foreach($this->properties as $property) {
            if(str_starts_with($property, ':')) {
                $value = $this->get(substr($property, 1), false);
                $this->params[substr($property, 1)] = $value;
            } else {
                $this->params[$property] = $this->get($property);
            }
        }
    }
}

?>