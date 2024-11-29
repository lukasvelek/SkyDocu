<?php

namespace App\UI\FormBuilder2;

/**
 * Form text area
 * 
 * @author Lukas Velek
 */
class TextArea extends AInteractableElement {
    private string $name;
    private ?string $content;

    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct();

        $this->name = $name;
        $this->content = null;
    }

    /**
     * Sets the textarea content
     * 
     * @param ?string $content Element content
     */
    public function setContent(?string $content) {
        $this->content = $content;
    }

    /**
     * Renders the element to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        $code = '<textarea ';

        if(!array_key_exists('name', $this->attributes)) {
            $this->addAttribute('name', $this->name);
        }
        if(!array_key_exists('id', $this->attributes)) {
            $this->addAttribute('id', $this->name);
        }

        $this->appendAttributesToCode($code);

        $code .= '>';

        if($this->content !== null) {
            $code .= $this->content;
        }

        $code .= '</textarea>';

        return $code;
    }
}

?>