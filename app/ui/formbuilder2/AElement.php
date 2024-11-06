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

    /**
     * Separates attributes with values and without values
     * 
     * @param array &$attributesWithValue Attributes with value array
     * @param array &$attributesWithoutValue Attributes without value array
     */
    protected function separateAttributes(array &$attributesWithValue, array &$attributesWithoutValue) {
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
}

?>