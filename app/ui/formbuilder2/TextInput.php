<?php

namespace App\UI\FormBuilder2;

class TextInput extends Input {
    public function __construct(string $name) {
        parent::__construct('text', $name);
    }
}

?>