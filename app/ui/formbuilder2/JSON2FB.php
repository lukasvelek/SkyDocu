<?php

namespace App\UI\FormBuilder2;

use App\Exceptions\GeneralException;

/**
 * JSON2FB is a helper-type class that helps with converting JSON form definition file to FormBuilder elements.
 * 
 * @author Lukas Velek
 */
class JSON2FB {
    private FormBuilder2 $form;
    
    private array $json;

    /**
     * Class constructor
     * 
     * @param FormBuilder2 $form FormBuilder2 instance
     * @param array $json JSON with form data
     */
    public function __construct(FormBuilder2 $form, array $json) {
        $this->form = $form;
        $this->json = $json;
    }

    /**
     * Processes the form
     */
    private function process() {
        $rootMandatoryAttributes = [
            'name',
            'elements',
            'action'
        ];

        foreach($rootMandatoryAttributes as $attr) {
            if(!array_key_exists($attr, $this->json)) {
                throw new GeneralException('Attribute \'' . $attr . '\' is not set in the form JSON.');
            }
        }

        $this->form->setName($this->json['name']);
        $this->form->setComponentName('form_' . $this->json['name']);

        $this->form->setAction($this->json['action']);

        $this->processElements($this->json['elements']);
    }

    /**
     * Processes all elements
     */
    private function processElements(array $elements) {
        $elementMandatoryAttributes = [
            'name',
            'type'
        ];

        foreach($elements as $element) {
            foreach($elementMandatoryAttributes as $attr) {
                if(!array_key_exists($attr, $element)) {
                    throw new GeneralException('Attribute \'' . $attr . '\' is not set in the form JSON.');
                }
            }

            $name = $element['name'];
            $label = null;
            if(array_key_exists('label', $element)) {
                $label = $element['label'];
            }

            $elem = null;

            switch($element['type']) {
                case 'text':
                    $elem = $this->form->addTextInput($name, $label);
                    break;
                
                case 'password':
                    $elem = $this->form->addPasswordInput($name, $label);
                    break;

                case 'number':
                    $elem = $this->form->addNumberInput($name, $label);
                    break;

                case 'select':
                    $elem = $this->form->addSelect($name, $label);
                    break;

                case 'checkbox':
                    $elem = $this->form->addCheckboxInput($name, $label);
                    break;

                case 'date':
                    $elem = $this->form->addDateInput($name, $label);
                    break;

                case 'datetime':
                    $elem = $this->form->addDateTimeInput($name, $label);
                    break;

                case 'email':
                    $elem = $this->form->addEmailInput($name, $label);
                    break;

                case 'file':
                    $elem = $this->form->addFileInput($name, $label);
                    break;

                case 'textarea':
                    $elem = $this->form->addTextArea($name, $label);
                    break;

                case 'time':
                    $elem = $this->form->addTimeInput($name, $label);
                    break;

                case 'submit':
                    if(!array_key_exists('text', $element)) {
                        throw new GeneralException('Attribute \'text\' must be set if type is \'submit\'.');
                    } else {
                        $elem = $this->form->addSubmit($element['text'], 'btn_submit');
                    }
                    break;

                case 'button':
                    if(!array_key_exists('text', $element)) {
                        throw new GeneralException('Attribute \'text\' must be set if type is \'button\'.');
                    } else {
                        $elem = $this->form->addButton($element['text']);
                    }
                    break;

                case 'label':
                    if(!array_key_exists('text', $element)) {
                        throw new GeneralException('Attribute \'text\' must be set if type is \'label\'.');
                    } else {
                        $elem = $this->form->addLabel($name, $element['text']);
                    }
                    break;
            }

            if($elem !== null) {
                if(array_key_exists('attributes', $element)) {
                    foreach($element['attributes'] as $attrName => $attrValue) {
                        switch($attrName) {
                            case 'required':
                                if(method_exists($elem, 'setRequired')) {
                                    $elem->setRequired($attrValue);
                                } else {
                                    throw new GeneralException('Element \'' . $element['type'] . '\' does not support attribute \'' . $attrName . '\'.');
                                }

                                break;

                            case 'readonly':
                                if(method_exists($elem, 'setReadonly')) {
                                    $elem->setReadonly($attrValue);
                                } else {
                                    throw new GeneralException('Element \'' . $element['type'] . '\' does not support attribute \'' . $attrName . '\'.');
                                }

                                break;
                        }
                    }
                }

                if($elem instanceof Button && array_key_exists('onClick', $element)) {
                    $elem->setOnClick($element['onClick']);
                }
                
                if(array_key_exists('value', $element)) {
                    if(method_exists($elem, 'setValue')) {
                        $elem->setValue($element['value']);
                    }
                }

                if(array_key_exists('values', $element) && ($elem instanceof Select)) {
                    foreach($element['values'] as $value => $text) {
                        $isSelected = false;

                        if(array_key_exists('selectedValue', $element)) {
                            if($value == $element['selectedValue']) {
                                $isSelected = true;
                            }
                        }

                        $elem->addRawOption($value, $text, $isSelected);
                    }
                }
            }
        }
    }

    /**
     * Renders the form to HTML code
     */
    public function render(): string {
        $this->process();

        return $this->form->render();
    }

    /**
     * Returns the instance of FormBuilder2 with all the elements
     */
    public function getFormBuilder(): FormBuilder2 {
        $this->process();

        return $this->form;
    }
}

?>