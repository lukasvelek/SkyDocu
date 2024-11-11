<?php

namespace App\UI\FormBuilder2;

use App\Core\Http\HttpRequest;
use App\UI\AComponent;

/**
 * FormBuilder allows building forms for interaction with the server
 * 
 * @author Lukas Velek
 */
class FormBuilder2 extends AComponent {
    /**
     * @var array<string, AElement>
     */
    private array $elements;
    /**
     * @var array<string, Label>
     */
    private array $labels;
    private string $name;
    private array $action;
    private string $method;
    
    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     */
    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->elements = [];
        $this->name = 'MyForm';
        $this->action = [];
        $this->method = 'POST';
    }

    /**
     * Sets form name
     * 
     * @param string $name Form name
     */
    public function setName(string $name) {
        $this->name = $name;
    }

    /**
     * Sets form action
     * 
     * @param array $action Form action
     */
    public function setAction(array $action) {
        $this->action = $action;
    }

    /**
     * Sets form method
     * 
     * @param string $method Form method
     */
    public function setMethod(string $method = 'POST') {
        $this->method = $method;
    }

    /**
     * Renders the form to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        $template = $this->getTemplate(__DIR__ . '/form.html');
        $template->form = $this->build();
        
        return $template->render()->getRenderedContent();
    }

    /**
     * Build the inner form (the form itself) and returns its HTML code
     * 
     * @return string HTML code
     */
    private function build() {
        $form = new Form($this->name);
        $form->setAction($this->action);
        $form->setMethod($this->method);

        foreach($this->elements as $name => $element) {
            $label = null;
            if(array_key_exists($name, $this->labels)) {
                $label = $this->labels[$name];
            }

            if($element instanceof AInteractableElement) {
                if($element->isRequired()) {
                    $label->setRequired();
                }
            }

            $row = new Row($label, $element, 'row_' . $name);

            if($element->isHidden()) {
                $row->hide();
            }

            $form->addRow($row);
        }

        return $form->render();
    }

    /**
     * Adds label
     * 
     * @param string $name Element name
     * @param string $text Label text
     * @return Label Label instance
     */
    public function addLabel(string $name, string $text) {
        $lbl = new Label($name, $text);
        $this->elements[$name] = &$lbl;
        return $lbl;
    }

    /**
     * Adds single-line text input
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @return TextInput TextInput instance
     */
    public function addTextInput(string $name, ?string $label = null) {
        $ti = new TextInput($name);

        $this->elements[$name] = &$ti;

        $this->processLabel($name, $label);

        return $ti;
    }

    /**
     * Adds email input
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @return EmailInput EmailInput instance
     */
    public function addEmailInput(string $name, ?string $label = null) {
        $ei = new EmailInput($name);

        $this->elements[$name] = &$ei;

        $this->processLabel($name, $label);

        return $ei;
    }

    /**
     * Adds password input
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @return PasswordInput PasswordInput instance
     */
    public function addPasswordInput(string $name, ?string $label = null) {
        $pi = new PasswordInput($name);

        $this->elements[$name] = &$pi;

        $this->processLabel($name, $label);

        return $pi;
    }

    /**
     * Adds number input
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @return NumberInput NumberInput instance
     */
    public function addNumberInput(string $name, ?string $label = null) {
        $ni = new NumberInput($name);

        $this->elements[$name] = &$ni;

        $this->processLabel($name, $label);

        return $ni;
    }

    /**
     * Adds datetime input
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @return DateTimeInput DateTimeInput instance
     */
    public function addDateTimeInput(string $name, ?string $label = null) {
        $dti = new DateTimeInput($name);
        
        $this->elements[$name] = &$dti;

        $this->processLabel($name, $label);

        return $dti;
    }

    public function addCheckboxInput(string $name, ?string $label = null) {
        $ci = new CheckboxInput($name);

        $this->elements[$name] = &$ci;

        $this->processLabel($name, $label);

        return $ci;
    }
    
    /**
     * Adds multi-line text input
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @return TextArea TextArea instance
     */
    public function addTextArea(string $name, ?string $label = null) {
        $ta = new TextArea($name);

        $this->elements[$name] = &$ta;

        $this->processLabel($name, $label);

        return $ta;
    }

    /**
     * Adds form submit button
     * 
     * @param string $text Button text
     * @param string $name Button name
     * @return SubmitButton SubmitButton instance
     */
    public function addSubmit(string $text = 'Submit', string $name = 'btn_submit') {
        $sb = new SubmitButton($name, $text);

        $this->elements[$name] = &$sb;

        return $sb;
    }

    /**
     * Adds form general button
     * 
     * @param string $text Button text
     * @return Button Button instance
     */
    public function addButton(string $text) {
        $b = new Button('button', $text);

        $i = 0;
        foreach($this->elements as $name => $value) {
            if(str_contains($name, 'btn')) {
                $i++;
            }
        }

        $this->elements['btn' . $i] = &$b;

        return $b;
    }

    /**
     * Adds form select
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @return Select
     */
    public function addSelect(string $name, ?string $label = null) {
        $s = new Select($name);

        $this->elements[$name] = &$s;

        $this->processLabel($name, $label);

        return $s;
    }

    /**
     * If $label is not null then a label is created and associated to given element $name
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     */
    private function processLabel(string $name, ?string $label) {
        if($label !== null) {
            $lbl = new Label($name, $label);
            $this->labels[$name] = $lbl;
        }
    }

    /**
     * Returns all form elements
     * 
     * @return array
     */
    public function getElements() {
        return $this->elements;
    }

    public static function createFromComponent(AComponent $component) {}
}

?>