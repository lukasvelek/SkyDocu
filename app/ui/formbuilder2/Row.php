<?php

namespace App\UI\FormBuilder2;

use App\UI\IRenderable;

/**
 * Form layout row
 * 
 * @author Lukas Velek
 */
class Row implements IRenderable {
    private ?Label $label;
    private ?AElement $element;
    private string $id;
    private bool $hide;

    /**
     * Class constructor
     * 
     * @param ?Label $label Label instance or null
     * @param ?AElement $element Element instance or null
     */
    public function __construct(?Label $label, ?AElement $element, string $id) {
        $this->label = $label;
        $this->element = $element;
        $this->id = $id;
        $this->hide = false;
    }

    /**
     * Sets the label
     * 
     * @param Label $label Label instance
     */
    public function setLabel(Label $label) {
        $this->label = $label;
    }

    /**
     * Sets the element
     * 
     * @param AElement $element Element instance
     */
    public function setElement(AElement $element) {
        $this->element = $element;
    }

    /**
     * Renders the row to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        $code = '<div id="' . $this->id . '"';

        if($this->hide) {
            $code .= ' style="display: none;"';
        }

        $code .= '><div class="row">';

        if($this->label !== null) {
            $code .= '<div class="col-md">' . $this->label->render() . '</div>';
        }
        if($this->element !== null) {
            $code .= '<div class="col-md">' . $this->element->render() . '</div>';
        }

        $code .= '</div><br></div>';

        return $code;
    }

    public function hide() {
        $this->hide = true;
    }
}

?>