<?php

namespace App\UI\FormBuilder2;

/**
 * Form label
 * 
 * @author Lukas Velek
 */
class Label extends AElement {
    private string $name;
    private string $text;
    private string $for;
    private bool $required;
    private bool $isTitle;

    /**
     * Class constructor
     * 
     * @param string $name Element name
     * @param string $text Label text
     */
    public function __construct(string $name, string $text) {
        parent::__construct();

        $this->text = $text;
        $this->for = $name;
        $this->name = 'lbl_' . $name;
        $this->required = false;
        $this->isTitle = false;
    }

    /**
     * Sets that the element is required
     * 
     * @param bool $required True if the element is required
     */
    public function setRequired(bool $required = true): static {
        $this->required = $required;
        return $this;
    }

    /**
     * Sets that the element is a title
     * 
     * @param bool $title Is the element a title?
     */
    public function setTitle(bool $title = true): static {
        $this->isTitle = $title;
        return $this;
    }

    /**
     * Renders the form element
     * 
     * @return string HTML code
     */
    public function render() {
        $code = '<label id="' . $this->name . '" for="' . $this->for . '" title="' . (str_ends_with($this->text, ':') ? substr($this->text, 0, -1) : $this->text) . '">';

        if($this->isTitle) {
            $code .= '<span style="font-size: 20px">' . $this->text . '</span>';
        } else {
            $code .= $this->text;
        }

        if($this->required) {
            $code .= ' <span style="color: red" title="This field is required">*</span>';
        }

        $code .= '</label>';

        return $code;
    }
}

?>