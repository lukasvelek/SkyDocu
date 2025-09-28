<?php

namespace App\UI\FormBuilder2;

/**
 * Common class for all inputs in a form
 * 
 * @author Lukas Velek
 */
abstract class AInput extends AInteractableElement {
    private string $type;
    
    protected string $name;
    protected array $additionalCode;

    public function __construct(string $type, string $name) {
        parent::__construct();

        $this->type = $type;
        $this->name = $name;
        $this->additionalCode = [];
    }

    /**
     * Sets value
     * 
     * @param mixed $value Value
     */
    public function setValue(mixed $value) {
        $this->addAttribute('value', $value);
        return $this;
    }

    public function render() {
        $code = '<input type="' . $this->type . '" ';

        if(!array_key_exists('name', $this->attributes)) {
            $this->addAttribute('name', $this->name);
        }
        if(!array_key_exists('id', $this->attributes)) {
            $this->addAttribute('id', $this->name);
        }

        $this->appendAttributesToCode($code);

        $code .= '>';

        if(!empty($this->additionalCode)) {
            $code .= implode('', $this->additionalCode);
        }

        return $code;
    }
}

?>