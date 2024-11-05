<?php

namespace App\UI\FormBuilder2;

class TextArea extends AInteractableElement {
    private string $name;
    private ?string $content;

    public function __construct(string $name) {
        parent::__construct();

        $this->name = $name;
        $this->content = null;
    }

    public function setContent(?string $content) {
        $this->content = $content;
    }

    public function render() {
        $code = '<textarea ';

        if(!array_key_exists('name', $this->attributes)) {
            $this->addAttribute('name', $this->name);
        }
        if(!array_key_exists('id', $this->attributes)) {
            $this->addAttribute('id', $this->name);
        }

        $nullValues = [];
        foreach($this->attributes as $name => $value) {
            if($value === null) {
                $nullValues[] = $name;
            } else {
                $code .= $name . '="' . $value . '" ';
            }
        }

        $code .= implode(' ', $nullValues) . '>';

        if($this->content !== null) {
            $code .= $this->content;
        }

        $code .= '</textarea>';

        return $code;
    }
}

?>