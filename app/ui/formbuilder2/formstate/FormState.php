<?php

namespace App\UI\FormBuilder2\FormState;

/**
 * FormState is a class that represents single form element state
 * 
 * @author Lukas Velek
 */
class FormState {
    private string $name;

    public bool $isRequired;
    public bool $isHidden;
    public bool $isReadonly;
    public mixed $defaultValue;
    public mixed $value;

    /**
     * Class constructor
     * 
     * @param string $name Form element name
     */
    public function __construct(string $name) {
        $this->name = $name;
        $this->isRequired = false;
        $this->isHidden = false;
        $this->isReadonly = false;
        $this->defaultValue = null;
        $this->value = null;
    }
}

?>