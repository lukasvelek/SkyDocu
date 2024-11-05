<?php

namespace App\UI\FormBuilder2;

/**
 * Commont interactable form element
 * 
 * @author Lukas Velek
 */
abstract class AInteractableElement extends AElement {
    protected array $modifiers;

    /**
     * Class constructor
     */
    protected function __construct() {
        parent::__construct();
        
        $this->modifiers = [];
    }

    /**
     * Sets placeholder
     * 
     * @param string $text Placeholder text
     * @return self
     */
    public function setPlaceholder(string $text) {
        $this->addAttribute('placeholder', $text);
        return $this;
    }

    /**
     * Sets that the element is required
     * 
     * @param bool $required True if the element is required
     * @return self
     */
    public function setRequired(bool $required = true) {
        $this->commonChangeUIParam('required', $required);
        $this->modifiers[] = 'required';
        return $this;
    }

    /**
     * Sets that the element is disabled
     * 
     * @param bool $disabled True if the element is disabled
     * @return self
     */
    public function setDisabled(bool $disabled = true) {
        $this->commonChangeUIParam('disabled', $disabled);
        $this->modifiers[] = 'disabled';
        return $this;
    }

    /**
     * Is the element required?
     * 
     * @return bool True or false
     */
    public function isRequired() {
        return in_array('required', $this->modifiers);
    }

    /**
     * Is the element disabled?
     * 
     * @return bool True or false
     */
    public function isDisabled() {
        return in_array('disabled', $this->modifiers);
    }

    /**
     * Adds UI form element modifier
     * 
     * @param string $name Attribute name
     * @param bool $use Is the attribute enabled -> should it be added?
     */
    private function commonChangeUIParam(string $name, bool $use = true) {
        if($use) {
            $this->attributes[$name] = null;
        } else {
            if(array_key_exists($name, $this->attributes)) {
                unset($this->attributes[$name]);
            }
        }
    }
}

?>