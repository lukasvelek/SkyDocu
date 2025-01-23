<?php

namespace App\UI\FormBuilder2;

/**
 * Form date input
 * 
 * @author Lukas Velek
 */
class DateInput extends AInput {
    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('date', $name);
    }

    /**
     * Sets the minimum value
     * 
     * @param string $minimum Minimum value
     */
    public function setMinimum(string $minimum): static {
        return $this->addAttribute('min', $minimum);
    }

    /**
     * Sets the maximum value
     * 
     * @param string $maximum Maximum value
     */
    public function setMaximum(string $maximum): static {
        return $this->addAttribute('max', $maximum);
    }
}

?>