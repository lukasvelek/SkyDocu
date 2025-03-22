<?php

namespace App\UI\ListBuilder;

use App\UI\HTML\HTML;

/**
 * Common class for list elements
 * 
 * @author Lukas Velek
 */
abstract class AListElement implements IListHTMLOutput {
    protected array $attributes;

    /**
     * Class constructor
     */
    protected function __construct() {
        $this->attributes = [];
    }

    /**
     * Appends attributes to HTML
     * 
     * @param HTML &$html
     */
    protected function appendAttributesToHtml(HTML &$html) {
        foreach($this->attributes as $key => $value) {
            $html->addAtribute($key, $value);
        }
    }
}

?>