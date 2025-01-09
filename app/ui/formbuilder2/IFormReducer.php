<?php

namespace App\UI\FormBuilder2;

/**
 * Common interface for form reducers
 * 
 * @author Lukas Velek
 */
interface IFormReducer {
    /**
     * Applies form reducer
     * 
     * @param FormBuilder2 &$form FormBuilder2 instance
     */
    function applyReducer(FormBuilder2 &$form);
}

?>