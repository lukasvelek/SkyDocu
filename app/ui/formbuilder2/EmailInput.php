<?php

namespace App\UI\FormBuilder2;

/**
 * Form email input
 * 
 * @author Lukas Velek
 */
class EmailInput extends Input {
    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('email', $name);
    }
}

?>