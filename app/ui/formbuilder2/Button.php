<?php

namespace App\UI\FormBuilder2;

/**
 * General form button
 * 
 * @author Lukas Velek
 */
class Button extends ACommonButton {
    private string $type;
    private string $text;

    /**
     * Class constructor
     * 
     * @param string $type Button type
     * @param string $text Button text
     */
    public function __construct(string $type, string $text) {
        parent::__construct();

        $this->type = $type;
        $this->text = $text;
    }

    /**
     * Sets on click action
     * 
     * @param string $onClick On click action
     */
    public function setOnClick(string $onClick) {
        $this->addAttribute('onclick', $onClick);
        return $this;
    }

    /**
     * Renders the element to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        $code = '<button type="' . $this->type . '"';

        $this->appendAttributesToCode($code);

        $code .= '>' . $this->text . '</button>';

        return $code;
    }
}

?>