<?php

namespace App\UI\FormBuilder2;

class Label extends AElement {
    private string $name;
    private string $text;
    private string $for;

    public function __construct(string $name, string $text) {
        $this->text = $text;
        $this->for = $name;
        $this->name = 'lbl_' . $name;
    }

    public function render() {
        return '<label id="' . $this->name . '" for="' . $this->for . '">' . $this->text . '</label>';
    }
}

?>