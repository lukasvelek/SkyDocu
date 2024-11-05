<?php

namespace App\UI\FormBuilder2;

abstract class AInteractableElement extends AElement {
    protected array $modifiers;

    protected function __construct() {
        parent::__construct();
        
        $this->modifiers = [];
    }

    public function setPlaceholder(string $text) {
        $this->addAttribute('placeholder', $text);
        return $this;
    }

    public function setRequired(bool $required = true) {
        $this->commonChangeUIParam('required', $required);
        $this->modifiers[] = 'required';
        return $this;
    }

    public function setDisabled(bool $disabled = true) {
        $this->commonChangeUIParam('disabled', $disabled);
        $this->modifiers[] = 'disabled';
        return $this;
    }

    public function isRequired() {
        return in_array('required', $this->modifiers);
    }

    public function isDisabled() {
        return in_array('disabled', $this->modifiers);
    }

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