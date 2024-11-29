<?php

namespace App\UI\FormBuilder2;

/**
 * Form number input
 * 
 * @author Lukas Velek
 */
class NumberInput extends Input {
    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('number', $name);
    }

    /**
     * Sets minimum
     * 
     * @param int $min Minimum
     */
    public function setMin(int $min) {
        $this->addAttribute('min', $min);
        return $this;
    }

    /**
     * Sets maximum
     * 
     * @param int $max Maximum
     */
    public function setMax(int $max) {
        $this->addAttribute('max', $max);
        return $this;
    }
}

?>