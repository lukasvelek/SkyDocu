<?php

namespace App\UI\FormBuilder2\FormState;

use App\UI\FormBuilder2\AElement;
use App\UI\FormBuilder2\AInput;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\FormBuilder2\Select;

/**
 * Handles applying state list to the form
 * 
 * @author Lukas Velek
 */
class StateListToForm {
    private FormBuilder2 $form;
    private FormStateList $stateList;
    
    /**
     * Class constructor
     * 
     * @param FormBuilder2 &$form FormBuilder2 instance
     * @param FormStateList $stateList FormStateList instance
     */
    public function __construct(FormBuilder2 &$form, FormStateList $stateList) {
        $this->form = $form;
        $this->stateList = $stateList;
    }

    /**
     * Applies state list to the form
     */
    public function apply() {
        $keys = $this->stateList->getKeys();

        foreach($keys as $key) {
            $element = &$this->form->elements[$key];

            $element->isHidden = $this->stateList->$key->isHidden;
            $element->isRequired = $this->stateList->$key->isRequired;
            
            $this->applyElementAttribute('isReadonly', $key, $element, 'readonly');

            if($element instanceof Select) {
                $element->setSelectedValue($this->stateList->$key->defaultValue);
            } else if($element instanceof AInput) {
                $element->setValue($this->stateList->$key->defaultValue);
            }
        }
    }

    /**
     * Applies element attribute
     * 
     * @param string $attributeName Name of the attribute in the StateList
     * @param string $key Element name
     * @param AElement &$element Element
     * @param ?string $formAttributeName Name of the attribute in the HTML form - if null is passed then $attributeName is used
     */
    private function applyElementAttribute(string $attributeName, string $key, AElement &$element, ?string $formAttributeName = null) {
        $formName = $formAttributeName ?? $attributeName;

        if($this->stateList->$key->$attributeName) {
            if(!array_key_exists($formName, $element->attributes)) {
                $element->attributes[$formName] = null;
            }
        } else {
            if(array_key_exists($formName, $element->attributes)) {
                unset($element->attributes[$formName]);
            }
        }
    }
}

?>