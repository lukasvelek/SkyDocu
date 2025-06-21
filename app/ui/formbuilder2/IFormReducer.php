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

    /**
     * Applies form reducer on form startup -> before the form is rendered to the user
     * 
     * @param FormStateList &$stateList
     */
    function applyOnStartupReducer(FormStateList &$stateList);

    /**
     * Applies form reducer after the form has been submitted and is reopened
     * 
     * @param FormStateList &$stateList
     */
    function applyAfterSubmitOnOpenReducer(FormStateList &$stateList);
}

?>