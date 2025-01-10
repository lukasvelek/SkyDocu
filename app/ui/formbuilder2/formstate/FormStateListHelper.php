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

            foreach($withValue as $key => $value) {
                if(isset($stateList->$name->$key)) {
                    $stateList->$name->$key = $value;
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

    /**
     * Creates StateList from state list returned from JS AJAX call
     * 
     * @param HttpRequest $request
     * @return FormStateList
     */
    public function createStateListFromJsState(HttpRequest $request) {
        $stateList = new FormStateList();

        if(!array_key_exists('elements', $request->query) && !array_key_exists('state', $request->query)) {
            return $stateList;
        }

        $states = $request->query['elements'];

        foreach($states as $name) {
            $stateList->addElement($name);

            if(isset($request->query['state'][$name]['hidden'])) {
                $stateList->$name->isHidden = ($request->query['state'][$name]['hidden'] == 'true');
            }
            if(isset($request->query['state'][$name]['required'])) {
                $stateList->$name->isRequired = ($request->query['state'][$name]['required'] == 'true');
            }
            if(isset($request->query['state'][$name]['readonly'])) {
                $stateList->$name->isReadonly = ($request->query['state'][$name]['readonly'] == 'true');
            }
            if(isset($request->query['state'][$name]['value'])) {
                $stateList->$name->value = ($request->query['state'][$name]['value'] != 'null' ? $request->query['state'][$name]['value'] : null);
            }
        }

        return $stateList;
    }
}

?>