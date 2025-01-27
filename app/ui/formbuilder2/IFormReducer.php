<?php

namespace App\UI\FormBuilder2;

use App\UI\FormBuilder2\FormState\FormStateList;

/**
 * Common interface for form reducers
 * 
 * @author Lukas Velek
 */
interface IFormReducer {
    /**
     * Applies form reducer
     * 
     * @param FormStateList &$stateList
     */
    function applyReducer(FormStateList &$stateList);
}

?>