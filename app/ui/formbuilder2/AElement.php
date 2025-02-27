<?php

namespace App\UI\FormBuilder2;

use App\UI\IRenderable;

/**
 * Common form element
 * 
 * @author Lukas Velek
 */
abstract class AElement implements IRenderable {
    public array $attributes;
    public bool $isHidden;
    public bool $isRequired;

    /**
     * Class constructor
     */
    protected function __construct() {
        $this->attributes = [];
        $this->isHidden = false;
        $this->isRequired = false;
    }

    /**
     * Adds an attribute
     * 
     * @param string $name Attribute name
     * @param mixed $value Value or null if it is a modifier
     */
    public function addAttribute(string $name, mixed $value = null): static {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Removes an attribute
     * 
     * @param string $name Attribute name
     */
    public function removeAttribute(string $name): static {
        if(array_key_exists($name, $this->attributes)) {
            unset($this->attributes[$name]);
        }
        return $this;
    }

    /**
     * Separates attributes with values and without values
     * 
     * @param array &$attributesWithValue Attributes with value array
     * @param array &$attributesWithoutValue Attributes without value array
     */
    public function separateAttributes(array &$attributesWithValue, array &$attributesWithoutValue) {
        foreach($this->attributes as $name => $value) {
            if($value === null) {
                $attributesWithoutValue[] = $name;
            } else {
                $attributesWithValue[] = $name . '="' . $value . '"';
            }
        }
    }

    /**
     * Appends separated attributes to passed code
     * 
     * @param string &$code Code
     */
    protected function appendAttributesToCode(string &$code) {
        $attributesWithValue = [];
        $attributesWithoutValue = [];

        $this->separateAttributes($attributesWithValue, $attributesWithoutValue);

        $code .= implode(' ', $attributesWithValue);
        $code .= implode(' ', $attributesWithoutValue);
    }

    /**
     * Hides the element
     */
    public function setHidden(bool $hidden = true): static {
        $this->isHidden = $hidden;
        return $this;
    }

    /**
     * Is the element hidden?
     * 
     * @return bool True if the element is hidden or false
     */
    public function isHidden() {
        return $this->isHidden;
    }

    /**
     * Sets the element required
     * 
     * @param bool $required True if required or false if not
     */
    public function setRequired(bool $required = true): static {
        $this->isRequired = $required;
        return $this;
    }

    /**
     * Is the element required?
     * 
     * @return bool True if the element is required or false
     */
    public function isRequired() {
        return $this->isRequired;
    }

    /**
     * Returns all atributes
     * 
     * @return array Attributes
     */
    public function getAttributes() {
        return $this->attributes;
    }
}

?>