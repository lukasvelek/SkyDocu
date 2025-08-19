<?php

namespace App\UI\FormBuilder2;

/**
 * HiddenInput represents an input of type hidden
 * 
 * @author Lukas Velek
 */
class HiddenInput extends AInput {
    public function __construct(string $name) {
        parent::__construct('hidden', $name);
    }
}