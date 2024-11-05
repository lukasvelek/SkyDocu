<?php

namespace App\UI\FormBuilder2;

use App\UI\IRenderable;

/**
 * Common form element
 * 
 * @author Lukas Velek
 */
abstract class AElement implements IRenderable {
    protected array $attributes;

    /**
     * Class constructor
     */
    protected function __construct() {
        $this->attributes = [];
    }

    /**
     * Adds an attribute
     * 
     * @param string $name Attribute name
     * @param mixed $value Value or null if it is a modifier
     * @return self
     */
    public function addAttribute(string $name, mixed $value = null) {
        $this->attributes[$name] = $value;
        return $this;
    }
}

?>