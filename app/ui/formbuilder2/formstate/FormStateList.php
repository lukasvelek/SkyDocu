<?php

namespace App\UI\FormBuilder2\FormState;

/**
 * FormStateList contains state information for multiple form elements
 * 
 * @author Lukas Velek
 */
class FormStateList {
    private array $_elements;
    private ?string $callingElementName;

    /**
     * Class costructor
     */
    public function __construct() {
        $this->_elements = [];
        $this->callingElementName = null;
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

    /**
     * Sets calling element's name
     * 
     * @param string $name Name
     */
    public function setCallingElementName(string $name) {
        $this->callingElementName = $name;
    }

    /**
     * Returns calling element's name
     */
    public function getCallingElementName(): ?string {
        return $this->callingElementName;
    }

    /**
     * Adds an element
     * 
     * @param string $name Element name
     */
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

    /**
     * Returns true if given key exists
     * 
     * @param string $key Key name
     */
    public function keyExists(string $key): bool {
        return array_key_exists($key, $this->_elements);
    }
}

?>