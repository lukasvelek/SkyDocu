<?php

namespace App\UI\FormBuilder2;

class HiddenInput extends AInput {
    public function __construct(string $name) {
        parent::__construct('hidden', $name);
    }
}