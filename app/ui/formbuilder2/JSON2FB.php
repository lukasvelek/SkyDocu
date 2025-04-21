<?php

namespace App\UI\FormBuilder2;

use App\Constants\AConstant;
use App\Exceptions\GeneralException;

/**
 * JSON2FB is a helper-type class that helps with converting JSON form definition file to FormBuilder elements.
 * 
 * @author Lukas Velek
 */
class JSON2FB {
    private const LABEL = 'label';
    private const TEXT = 'text';
    private const PASSWORD = 'password';
    private const NUMBER = 'number';
    private const SELECT = 'select';
    private const CHECKBOX = 'checkbox';
    private const DATE = 'date';
    private const DATETIME = 'datetime';
    private const TIME = 'time';
    private const FILE = 'file';
    private const EMAIL = 'email';
    private const TEXTAREA = 'textarea';
    private const SUBMIT = 'submit';
    private const BUTTON = 'button';
    private const USER_SELECT = 'userSelect';
    private const USER_SELECT_SEARCH = 'userSelectSearch';
    private const SELECT_SEARCH = 'selectSearch';

    private FormBuilder2 $form;
    
    private array $json;
    private array $skipAttributes;
    private array $skipElementAttributes;
    private bool $viewOnly;

    /**
     * Class constructor
     * 
     * @param FormBuilder2 $form FormBuilder2 instance
     * @param array $json JSON with form data
     */
    public function __construct(FormBuilder2 $form, array $json) {
        $this->form = $form;
        $this->json = $json;

        $this->skipAttributes = [];
        $this->skipElementAttributes = [];
        $this->viewOnly = false;
    }

    /**
     * Sets whether this is only a view render
     * 
     * @param bool $viewOnly View only
     */
    public function setViewOnly(bool $viewOnly = true) {
        $this->viewOnly = $viewOnly;

        $this->skipAttributes[] = 'action';
    }

    /**
     * Adds attributes to skip for given element
     * 
     * @param string $elementType Element type
     * @param string ...$attributes Attributes to skip
     */
    public function addSkipElementAttributes(string $elementType, string ...$attributes) {
        $this->skipElementAttributes[$elementType] = $attributes;
    }

    /**
     * Sets attributes to skip
     * 
     * @param array $skipAttributes Attributes to skip
     */
    public function setSkipAttributes(array $skipAttributes) {
        $this->skipAttributes = $skipAttributes;
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
            if(in_array($attr, $this->skipAttributes)) continue;

            if(!array_key_exists($attr, $this->json)) {
                throw new GeneralException('Attribute \'' . $attr . '\' is not set in the form JSON.');
            }
        }

        $this->form->setName($this->json['name']);
        $this->form->setComponentName('form_' . $this->json['name']);

        if(in_array('action', $this->skipAttributes) && array_key_exists('action', $this->json)) {
            $this->form->setAction($this->json['action']);
        }

        $this->processElements($this->json['elements']);

        if(array_key_exists('reducer', $this->json)) {
            $reducer = $this->json['reducer'];

            if(class_exists($reducer)) {
               $this->form->reducer = new $reducer($this->form->httpRequest);
               $this->form->setCallReducerOnChange();
            }
        }
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
                if(in_array($element['type'], $this->skipElementAttributes) && in_array($attr, $this->skipElementAttributes[$element['name']])) continue;

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
                case self::TEXT:
                    $elem = $this->form->addTextInput($name, $label);
                    break;
                
                case self::PASSWORD:
                    $elem = $this->form->addPasswordInput($name, $label);
                    break;

                case self::NUMBER:
                    $elem = $this->form->addNumberInput($name, $label);
                    break;

                case self::SELECT:
                    $elem = $this->form->addSelect($name, $label);
                    break;

                case self::CHECKBOX:
                    $elem = $this->form->addCheckboxInput($name, $label);
                    break;

                case self::DATE:
                    $elem = $this->form->addDateInput($name, $label);
                    break;

                case self::DATETIME:
                    $elem = $this->form->addDateTimeInput($name, $label);
                    break;

                case self::EMAIL:
                    $elem = $this->form->addEmailInput($name, $label);
                    break;

                case self::FILE:
                    $elem = $this->form->addFileInput($name, $label);
                    break;

                case self::TEXTAREA:
                    $elem = $this->form->addTextArea($name, $label);
                    break;

                case self::TIME:
                    $elem = $this->form->addTimeInput($name, $label);
                    break;

                case self::SUBMIT:
                    if(!array_key_exists('text', $element)) {
                        $this->throwExceptionForUnsetAttribute('text', $element['type']);
                    } else {
                        $elem = $this->form->addSubmit($element['text'], 'btn_submit');
                    }
                    break;

                case self::BUTTON:
                    if(!array_key_exists('text', $element)) {
                        $this->throwExceptionForUnsetAttribute('text', $element['type']);
                    } else {
                        $elem = $this->form->addButton($element['text']);
                    }
                    break;

                case self::LABEL:
                    if(!array_key_exists('text', $element)) {
                        $this->throwExceptionForUnsetAttribute('text', $element['type']);
                    } else {
                        $elem = $this->form->addLabel($name, $element['text']);
                    }
                    break;

                case self::USER_SELECT:
                    if(!array_key_exists('containerId', $element) && (!array_key_exists($element['type'], $this->skipElementAttributes) || (array_key_exists($element['type'], $this->skipElementAttributes) && !in_array('containerId', $this->skipElementAttributes[$element['type']])))) {
                        $this->throwExceptionForUnsetAttribute('containerId', $element['type']);
                    } else {
                        $elem = $this->form->addUserSelect($name, $label);
                    }
                    break;

                case self::USER_SELECT_SEARCH:
                    if(!array_key_exists('containerId', $element) && (!array_key_exists($element['type'], $this->skipElementAttributes) || (array_key_exists($element['type'], $this->skipElementAttributes) && !in_array('containerId', $this->skipElementAttributes[$element['type']])))) {
                        $this->throwExceptionForUnsetAttribute('containerId', $element['type']);
                    } else {
                        $elem = $this->form->addUserSelectSearch($name, $label);
                    }
                    break;

                case self::SELECT_SEARCH:
                    if(!array_key_exists('actionName', $element)) {
                        $this->throwExceptionForUnsetAttribute('actionName', $element['type']);
                    }
                    if(!array_key_exists('searchByLabel', $element)) {
                        $this->throwExceptionForUnsetAttribute('searchByLabel', $element['type']);
                    }

                    $elem = $this->form->addPresenterSelectSearch($element['actionName'], [], $element['name'], $element['searchByLabel'], $element['label']);
                    break;
            }

            if($elem !== null) {
                if(array_key_exists('attributes', $element)) {
                    foreach($element['attributes'] as $attrName) {
                        switch($attrName) {
                            case 'required':
                                if(method_exists($elem, 'setRequired')) {
                                    $elem->setRequired();
                                } else {
                                    $this->throwExceptionForUnsupportedAttribute($attrName, $element['type']);
                                }

                                break;

                            case 'readonly':
                                if(method_exists($elem, 'setReadonly')) {
                                    $elem->setReadonly();
                                } else {
                                    $this->throwExceptionForUnsupportedAttribute($attrName, $element['type']);
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

                if($elem instanceof Select) {
                    if(array_key_exists('values', $element)) {
                        foreach($element['values'] as $value => $text) {
                            $isSelected = false;
    
                            if(array_key_exists('selectedValue', $element)) {
                                if($value == $element['selectedValue']) {
                                    $isSelected = true;
                                }
                            }
    
                            $elem->addRawOption($value, $text, $isSelected);
                        }
                    } else if(array_key_exists('valuesFromConst', $element)) {
                        $const = $element['valuesFromConst'];

                        if(class_exists($const)) {
                            if(is_a($const, AConstant::class, true)) {
                                foreach($const::getAll() as $value => $text) {
                                    $elem->addRawOption($value, $text);
                                }
                            } else {
                                throw new GeneralException('Class \'' . $const . '\' does not extend \'AConstant\' abstract class.');
                            }
                        } else {
                            throw new GeneralException('Class \'' . $const . '\' does not exist.');
                        }
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

        return $this->form->renderElementsOnly();
    }

    /**
     * Returns the instance of FormBuilder2 with all the elements
     */
    public function getFormBuilder(): FormBuilder2 {
        $this->process();

        return $this->form;
    }

    /**
     * Throws exception when an unsupported attribute is set
     * 
     * @param string $attrName Attribute name
     * @param string $elementType Element type
     * @throws GeneralException
     */
    private function throwExceptionForUnsupportedAttribute(string $attrName, string $elementType) {
        throw new GeneralException('Element \'' . $elementType . '\' does not support attribute \'' . $attrName . '\'.');
    }

    /**
     * Throws exception when an attribute is not set
     * 
     * @param string $attrName Attribute name
     * @param string $elementType Element type
     * @throws GeneralException
     */
    private function throwExceptionForUnsetAttribute(string $attrName, string $elementType) {
        throw new GeneralException('Attribute \'' . $attrName . '\' must be set if type is \'' . $elementType . '\'.');
    }
}

?>