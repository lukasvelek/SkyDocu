<?php

namespace App\UI\FormBuilder2;

/**
 * Common class for all inputs in a form
 * 
 * @author Lukas Velek
 */
abstract class AInput extends AInteractableElement {
    private string $type;
    private string $name;

    public function __construct(string $type, string $name) {
        parent::__construct();

        $this->type = $type;
        $this->name = $name;
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

        return $code;
    }
}

?>