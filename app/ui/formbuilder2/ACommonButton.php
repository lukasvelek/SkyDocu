<?php

namespace App\UI\FormBuilder2;

/**
 * Common class for form buttons
 * 
 * @author Lukas Velek
 */
abstract class ACommonButton extends AElement {
    /**
     * Disables the button
     * 
     * @param bool $disabled True if the button is disabled
     */
    public function setDisabled(bool $disabled = true): static {
        if($disabled) {
            $this->addAttribute('disabled');
        } else {
            if(array_key_exists('disabled', $this->attributes)) {
                unset($this->attributes['disabled']);
            }
        }
        return $this;
    }
}

?>