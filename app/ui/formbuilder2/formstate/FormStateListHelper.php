<?php

namespace App\UI\FormBuilder2\FormState;

use App\UI\FormBuilder2\FormBuilder2;

/**
 * Helps with FormStateList
 * 
 * @author Lukas Velek
 */
class FormStateListHelper {
    /**
     * Class constructor
     */
    public function __construct() {}

    /**
     * Converts form to state list
     * 
     * @param FormBuilder2 $form Form instance
     * @return FormStateList StateList
     */
    public function convertFormToStateList(FormBuilder2 $form) {
        $stateList = new FormStateList();

        $elements = $form->getElements();

        foreach($elements as $name => $element) {
            $stateList->addElement($name);

            $stateList->$name->isHidden = $element->isHidden;
            $stateList->$name->isRequired = $element->isRequired;

            $withoutValue = $withValue = [];
            $element->separateAttributes($withValue, $withoutValue);

            foreach($withoutValue as $value) {
                if(isset($stateList->$name->$value)) {
                    $stateList->$name->$value = $value;
                }
            }
        }

        return $stateList;
    }

    /**
     * Applies state list to the form
     * 
     * @param FormBuilder2 &$form Form instance
     * @param FormStateList $stateList FormStateList instance
     */
    public function applyStateListToForm(FormBuilder2 &$form, FormStateList $stateList) {
        $keys = $stateList->getKeys();

        foreach($keys as $key) {
            $element = &$form->elements[$key];

            $element->isHidden = $stateList->$key->isHidden;
            $element->isRequired = $stateList->$key->isRequired;
            
            if($stateList->$key->isReadonly) {
                if(!array_key_exists('isReadonly', $element->attributes)) {
                    $element->attributes['isReadonly'] = null;
                }
            } else {
                if(array_key_exists('isReadonly', $element->attributes)) {
                    unset($element->attributes['isReadonly']);
                }
            }
        }
    }
}

?>