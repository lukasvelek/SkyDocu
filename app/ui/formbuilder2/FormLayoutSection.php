<?php

namespace App\UI\FormBuilder2;

/**
 * Creates an empty section in form for dynamic content
 * 
 * @author Lukas Velek
 */
class FormLayoutSection extends AElement {
    private string $name;

    public function __construct(string $name) {
        parent::__construct();

        $this->name = $name;
    }

    public function render() {
        $code = '<div id="' . $this->name . '"></div>';

        return $code;
    }
}

?>