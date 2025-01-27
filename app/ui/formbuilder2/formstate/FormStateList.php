<?php

namespace App\UI\FormBuilder2\FormState;

/**
 * FormStateList contains state information for multiple form elements
 * 
 * @author Lukas Velek
 */
class FormStateList {
    private array $_elements;

    /**
     * Class costructor
     */
    public function __construct() {
        $this->_elements = [];
    }

    public function __set(string $key, mixed $value) {
        $this->_elements[$key] = $value;
    }

    public function __get(string $key) {
        if(array_key_exists($key, $this->_elements)) {
            return $this->_elements[$key];
        } else {
            return null;
        }
    }

    public function addElement(string $name) {
        $this->_elements[$name] = new FormState($name);
    }

    /**
     * Returns form element keys
     * 
     * @return array Element keys
     */
    public function getKeys() {
        return array_keys($this->_elements);
    }

    /**
     * Returns form element data
     * 
     * @return array Elements
     */
    public function getAll() {
        return $this->_elements;
    }
}

?>