<?php

namespace App\UI\FormBuilder2;

/**
 * Form text input
 * 
 * @author Lukas Velek
 */
class TextInput extends AInput {
    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('text', $name);
    }
}

?>