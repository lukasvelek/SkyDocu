<?php

namespace App\UI\FormBuilder2\FormState;

use App\Core\Http\HttpRequest;
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
        $stateListToForm = new StateListToForm($form, $stateList);
        $stateListToForm->apply();
    }

    public function createStateListFromJsState(HttpRequest $request) {
        $stateList = new FormStateList();

        if(!array_key_exists('elements', $request->query) && !array_key_exists('state', $request->query)) {
            return $stateList;
        }

        $states = $request->query['elements'];

        foreach($states as $name) {
            $stateList->addElement($name);

            $stateList->$name->isHidden = ($request->query['state'][$name]['hidden'] == 'true');
            $stateList->$name->isRequired = ($request->query['state'][$name]['required'] == 'true');
            $stateList->$name->isReadonly = ($request->query['state'][$name]['readonly'] == 'true');
        }

        return $stateList;
    }
}

?>