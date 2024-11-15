<?php

namespace App\UI\FormBuilder2;

/**
 * Form select option
 * 
 * @author Lukas Velek
 */
class SelectOption extends AElement {
    private string $text;
    private string $value;
    private bool $selected;

    /**
     * Class constructor
     * 
     * @param string $value Option value
     * @param string $text Option text
     */
    public function __construct(string $value, string $text) {
        parent::__construct();

        $this->text = $text;
        $this->value = $value;
        $this->selected = false;
    }

    /**
     * Sets the option as selected
     * 
     * @param bool $selected True if the option is selected
     */
    public function setSelected(bool $selected = true) {
        $this->selected = $selected;
    }

    /**
     * Returns the option value
     * 
     * @return string Option value
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Sets the option text
     * 
     * @param string $text New text
     */
    public function setText(string $text) {
        $this->text = $text;
    }

    /**
     * Renders the element to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        return '<option value="' . $this->value . '"' . ($this->selected ? ' selected' : '') . '>' . $this->text . '</option>';
    }
}

?>