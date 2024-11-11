<?php

namespace App\UI\FormBuilder2;

/**
 * Form checkbox input
 * 
 * @author Lukas Velek
 */
class CheckboxInput extends Input {
    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('checkbox', $name);
    }
}

?>