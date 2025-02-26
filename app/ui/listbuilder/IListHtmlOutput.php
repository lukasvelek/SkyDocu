<?php

namespace App\UI\ListBuilder;

use App\UI\HTML\HTML;

/**
 * Common interface that makes sure it's implementations can output HTML code
 * 
 * @author Lukas Velek
 */
interface IListHTMLOutput {
    /**
     * Outputs HTML code
     * 
     * @return HTML
     */
    function output(): HTML;
}

?>