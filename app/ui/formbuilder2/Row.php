<?php

namespace App\UI\FormBuilder2;

use App\UI\IRenderable;

class Row implements IRenderable {
    private ?Label $label;
    private ?AElement $element;

    public function __construct(?Label $label, ?AElement $element) {
        $this->label = $label;
        $this->element = $element;
    }

    public function setLabel(Label $label) {
        $this->label = $label;
    }

    public function setElement(AElement $element) {
        $this->element = $element;
    }

    public function render() {
        $code = '<div class="row">';

        if($this->label !== null) {
            $code .= '<div class="col-md">' . $this->label->render() . '</div>';
        }
        if($this->element !== null) {
            $code .= '<div class="col-md">' . $this->element->render() . '</div>';
        }

        $code .= '</div>';

        return $code;
    }
}

?>