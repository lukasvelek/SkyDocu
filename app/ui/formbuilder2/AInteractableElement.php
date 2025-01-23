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
     */
    public function setPlaceholder(string $text): static {
        $this->addAttribute('placeholder', $text);
        return $this;
    }

    /**
     * Sets that the element is required
     * 
     * @param bool $required True if the element is required
     */
    public function setRequired(bool $required = true): static {
        $this->commonChangeUIParam('required', $required);
        $this->modifiers[] = 'required';
        parent::setRequired($required);
        return $this;
    }

    /**
     * Sets that the element is disabled
     * 
     * @param bool $disabled True if the element is disabled
     */
    public function setDisabled(bool $disabled = true): static {
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

    /**
     * Adds on change attribute
     * 
     * @param string $jsHandler JS handler
     */
    public function onChange(string $jsHandler): static {
        $this->addAttribute('onclick', $jsHandler);
        return $this;
    }
}

?>