<?php

namespace App\UI\FormBuilder2;

/**
 * Form password input
 * 
 * @author Lukas Velek
 */
class PasswordInput extends AInput {
    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('password', $name);
    }
}

?>