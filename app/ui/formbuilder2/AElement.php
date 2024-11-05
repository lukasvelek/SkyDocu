<?php

namespace App\UI\FormBuilder2;

use App\UI\IRenderable;

abstract class AElement implements IRenderable {
    protected array $attributes;

    protected function __construct() {
        $this->attributes = [];
    }

    public function addAttribute(string $name, mixed $value = null) {
        $this->attributes[$name] = $value;
    }

    public function setRequired(bool $required = true) {
        $this->commonChangeUIParam('required', $required);
    }

    public function setDisabled(bool $disabled = true) {
        $this->commonChangeUIParam('disabled', $disabled);
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