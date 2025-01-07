<?php

namespace App\UI\FormBuilder2;

/**
 * Form checkbox input
 * 
 * @author Lukas Velek
 */
class CheckboxInput extends AInput {
    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('checkbox', $name);
    }

    /**
     * Sets the checkbox as checked
     * 
     * @param bool $checked True if the checkbox is checked or false if not
     */
    public function setChecked(bool $checked = true) {
        if($checked) {
            $this->addAttribute('checked');
        }

        return $this;
    }
}

?>