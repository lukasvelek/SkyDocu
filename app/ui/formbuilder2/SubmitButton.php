<?php

namespace App\UI\FormBuilder2;

/**
 * Form submit button
 * 
 * @author Lukas Velek
 */
class SubmitButton extends ACommonButton {
    private string $name;
    private string $text;

    /**
     * Class constructor
     * 
     * @param string $name Button name
     * @param string $text Button text
     */
    public function __construct(string $name, string $text) {
        parent::__construct();

        $this->name = $name;
        $this->text = $text;

        $this->addAttribute('id', 'formSubmit');
    }

    /**
     * Renders the element to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        $code = '<input type="submit" value="' . $this->text . '" name="' . $this->name . '"';

        $this->appendAttributesToCode($code);

        $code .= '>';

        return $code;
    }
}

?>