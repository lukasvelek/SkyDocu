<?php

namespace App\UI\FormBuilder2;

use App\UI\IRenderable;

class Label extends AElement {
    private string $name;
    private string $text;
    private string $for;
    private bool $required;
    private bool $isTitle;

    public function __construct(string $name, string $text) {
        $this->text = $text;
        $this->for = $name;
        $this->name = 'lbl_' . $name;
        $this->required = false;
        $this->isTitle = false;
    }

    public function setRequired(bool $required = true) {
        $this->required = $required;
        return $this;
    }

    public function setTitle(bool $title = true) {
        $this->isTitle = $title;
        return $this;
    }

    public function render() {
        $code = '<label id="' . $this->name . '" for="' . $this->for . '">';

        if($this->isTitle) {
            $code .= '<span style="font-size: 20px">' . $this->text . '</span>';
        } else {
            $code .= $this->text;
        }

        if($this->required) {
            $code .= ' <span style="color: red">*</span>';
        }

        $code .= '</label>';

        return $code;
    }
}

?>