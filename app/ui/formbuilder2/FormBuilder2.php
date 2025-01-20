<?php

namespace App\UI\FormBuilder2;

use App\Core\AjaxRequestBuilder;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Core\Router;
use App\Modules\ModuleManager;
use App\UI\AComponent;
use App\UI\FormBuilder2\FormState\FormStateList;
use App\UI\FormBuilder2\FormState\FormStateListHelper;

/**
 * FormBuilder allows building forms for interaction with the server
 * 
 * @author Lukas Velek
 */
class FormBuilder2 extends AComponent {
    /**
     * @var array<string, AElement>
     */
    public array $elements;
    /**
     * @var array<string, Label>
     */
    private array $labels;
    private string $name;
    private array $action;
    private string $method;
    private array $scripts;
    private bool $callReducerOnChange;
    private bool $isPrerendered;
    private array $additionalLinkParams;

    private FormStateListHelper $stateListHelper;

    public ?IFormReducer $reducer;
    private Router $router;
    
    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     */
    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->stateListHelper = new FormStateListHelper();

        $this->elements = [];
        $this->name = 'MyForm';
        $this->action = [];
        $this->method = 'POST';
        $this->labels = [];
        $this->scripts = [];
        $this->reducer = null;
        $this->callReducerOnChange = false;
        $this->isPrerendered = false;
        $this->additionalLinkParams = [];

        $this->router = new Router();
    }

    /**
     * Sets if reducer should be called on every change
     * 
     * @param bool $callReducerOnChange
     */
    public function setCallReducerOnChange(bool $callReducerOnChange = true) {
        $this->callReducerOnChange = $callReducerOnChange;
    }

    /**
     * Adds form JS script code
     * 
     * @param AjaxRequestBuilder|string $code JS code
     */
    public function addScript(AjaxRequestBuilder|string $code) {
        if($code instanceof AjaxRequestBuilder) {
            $code = $code->build();
        }

        $code = '<script type="text/javascript">' . $code . '</script>';

        $this->scripts[] = $code;
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
        if($this->isPrerendered === false) {
            $this->prerender();
        }

        $template = $this->getTemplate(__DIR__ . '/form.html');
        $template->form = $this->build();
        $template->scripts = implode('', $this->scripts);
        
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
        $form->setAdditionalLinkParams($this->additionalLinkParams);

        if($this->reducer !== null && !$this->httpRequest->isAjax) {
            $stateList = $this->getStateList();
            $this->reducer->applyReducer($stateList);
            $this->applyStateList($stateList);
        }

        foreach($this->elements as $name => $element) {
            $row = $this->buildElement($name, $element);

            $form->addRow($row);
        }

        return $form->render();
    }

    public function prerender() {
        parent::prerender();

        if($this->callReducerOnChange && $this->reducer !== null) {
            $callArgs = [];

            foreach($this->httpRequest->query as $k => $v) {
                if(in_array($k, ['page', 'action', 'do', 'isComponent', 'isAjax', 'state', 'elements'])) continue;

                $callArgs[] = $v;
            }
            $code = 'function addOnChange() {';

            foreach(array_keys($this->elements) as $name) {
                $code .= '$("#' . $name . '").on("change", function() { ' . $this->componentName . '_onChange(\'' . implode('\', \'', $callArgs) . '\', \'null\'); });';
            }
    
            $code .= '}';
    
            $this->addScript($code);

            $this->addScript('addOnChange();');
        }

        $this->isPrerendered = true;
    }

    public function startup() {
        parent::startup();

        $attributes = [
            'hidden',
            'required',
            'readonly',
            'value'
        ];

        $code = 'function getFormState() {';

        foreach(array_keys($this->elements) as $name) {
            if($name == 'btn_submit') continue;
            foreach($attributes as $attr) {
                $code .= 'var ' . $name . '_' . $attr . ' = $("#' . $name . '").prop("' . $attr . '"); ';
            }
        }

        foreach(array_keys($this->elements) as $name) {
            if($name == 'btn_submit') continue;

            foreach($attributes as $attr) {
                if($attr == 'value') {
                    $code .= 'if(' . $name . '_' . $attr . ' == null) { ' . $name . '_attr = "null"; }';
                    continue;
                }

                $code .= 'if(' . $name . '_' . $attr . ' == null) { ' . $name . '_attr = false; }';
            }
        }

        $jsonArr = [];
        foreach(array_keys($this->elements) as $name) {
            if($name == 'btn_submit') continue;

            foreach($attributes as $attr) {
                $jsonArr[$name][$attr] = $name . '_' . $attr;
            }
        }

        $json = json_encode($jsonArr);

        foreach(array_keys($this->elements) as $name) {
            if($name == 'btn_submit') continue;

            foreach($attributes as $attr) {
                $json = str_replace('"' . $name . '_' . $attr . '"', $name . '_' . $attr, $json);
            }
        }

        $code .= 'const json = ' . $json . ';';
        $code .= 'return json;';

        $code .= '}';

        $this->presenter->addScript($code);

        $hArgs = [];
        $fArgs = [];
        $callArgs = [];

        foreach($this->httpRequest->query as $k => $v) {
            if(array_key_exists($k, $this->action)) continue;

            $hArgs[$k] = '_' . $k;
            $fArgs[] = '_' . $k;
            $callArgs[] = $v;
        }

        $hArgs['state'] = '_state';
        $fArgs[] = '_state';

        foreach(array_keys($this->elements) as $name) {
            if($name == 'btn_submit') continue;
            $hArgs['elements[]'][] = $name;
        }

        $actionParams = [];
        foreach($this->action as $k => $v) {
            if(in_array($k, ['page', 'action', 'do', 'isComponent', 'isAjax'])) continue;

            $actionParams[$k] = $v;
        }

        $arb = new AjaxRequestBuilder();

        $arb->setMethod('POST')
            ->setComponentAction($this->presenter, $this->componentName . '-onChange', $actionParams)
            ->setHeader($hArgs)
            ->setFunctionName($this->componentName . '_onChange')
            ->setFunctionArguments($fArgs)
            ->updateHTMLElement('form', 'form')
            ->setComponent()
            ->addBeforeAjaxOperation('
                _state = getFormState();
            ')
            ->enableLoadingAnimation('form')
        ;
        
        $this->presenter->addScript($code);

        $this->router->inject($this->presenter, new ModuleManager());
        if(!$this->router->checkEndpointExists($this->action)) {
            // throw exception
        }
    }

    /**
     * Builds a element row - label and the element itself
     * 
     * @param string $name Element name
     * @param AElement $element Element
     * @return Row Element row instance
     */
    protected function buildElement(string $name, AElement $element) {
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

        return $row;
    }

    /**
     * Adds empty layout section
     * 
     * @param string $name Section name
     */
    public function addLayoutSection(string $name) {
        $fls = new FormLayoutSection($name);

        $this->elements[$name] = &$fls;
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

    /**
     * Adds date input
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @return DateInput DateInput instance
     */
    public function addDateInput(string $name, ?string $label = null) {
        $di = new DateInput($name);
        
        $this->elements[$name] = &$di;

        $this->processLabel($name, $label);

        return $di;
    }

    /**
     * Adds checkbox input
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @return CheckboxInput CheckboxInput instance
     */
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
     * @return array<string, AElement>
     */
    public function getElements() {
        return $this->elements;
    }

    /**
     * Returns single element by name as a link
     * 
     * @param string $name Element name
     * @return \App\UI\FormBuilder2\AElement Element
     */
    public function getElement(string $name) {
        $el = &$this->elements[$name];
        return $el;
    }

    /**
     * Returns state list from the form
     * 
     * @return FormStateList
     */
    public function getStateList() {
        return $this->stateListHelper->convertFormToStateList($this);
    }

    /**
     * Applies state list to the form
     * 
     * @param FormStateList $stateList
     */
    public function applyStateList(FormStateList $stateList) {
        $this->stateListHelper->applyStateListToForm($this, $stateList);
    }

    public static function createFromComponent(AComponent $component) {
        $obj = new self($component->httpRequest);
        $obj->setApplication($component->app);
        $obj->setPresenter($component->presenter);
        return $obj;
    }

    /**
     * Handles on changes handler
     * 
     * @return JsonResponse
     */
    public function actionOnChange() {
        $stateList = $this->stateListHelper->createStateListFromJsState($this->httpRequest);

        if($this->reducer !== null) {
            $this->reducer->applyReducer($stateList);
        }

        $this->applyStateList($stateList);

        return new JsonResponse(['form' => $this->render()]);
    }

    /**
     * Sets additional link parameters
     * 
     * @param string $key Link key
     * @param mixed $data Link data
     */
    public function setAdditionalLinkParameters(string $key, mixed $data) {
        if($data === null) {
            return;
        }

        $this->additionalLinkParams[$key] = $data;
    }
}

?>