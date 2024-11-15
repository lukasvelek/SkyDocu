<?php

namespace App\UI\FormBuilder2;

/**
 * Form date input
 * 
 * @author Lukas Velek
 */
class DateInput extends Input {
    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('date', $name);
    }
}

?>