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
        return $this;
    }
}

?>