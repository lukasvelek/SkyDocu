<?php

namespace App\UI\FormBuilder2;

/**
 * Form time input
 * 
 * @author Lukas Velek
 */
class TimeInput extends AInput {
    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('time', $name);
    }
}

?>