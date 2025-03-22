<?php

namespace App\Helpers;

use App\Core\Http\FormRequest;

/**
 * FormHelper helps with processing forms
 * 
 * @author Lukas Velek
 */
class FormHelper {
    /**
     * Checks if checkbox is checked
     * 
     * @param FormRequest $fr
     * @param string $elementName
     * @return bool
     */
    public static function isCheckboxChecked(FormRequest $fr, string $elementName) {
        if($fr->isset($elementName) && $fr->{$elementName} == 'on') {
            return true;
        }

        return false;
    }
}

?>