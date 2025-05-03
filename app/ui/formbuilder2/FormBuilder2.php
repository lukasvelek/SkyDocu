<?php

namespace App\UI\FormBuilder2;

use App\Core\AjaxRequestBuilder;
use App\Core\HashManager;
use App\Core\Http\Ajax\Operations\CustomOperation;
use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\AAjaxRequest;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
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
    private bool $overrideReducerOnStartup = false;
    private bool $callAfterSubmitReducer = false;

    private FormStateListHelper $stateListHelper;

    public ?ABaseFormReducer $reducer;
    private Router $router;
    private bool $hasFile;
    
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
        $this->hasFile = false;

        $this->router = new Router();
    }

    /**
     * If set to true reducer isn't called on startup
     * 
     * @param bool $override Override reducer call on start up
     */
    public function setOverrideReducerCallOnStartup(bool $override = true) {
        $this->overrideReducerOnStartup = $override;
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
     * Sets if the after submit reducer method should be called
     * 
     * @param bool $callReducer
     */
    public function setCallAfterSubmitReducer(bool $callReducer = true) {
        $this->callAfterSubmitReducer = $callReducer;
    }

    /**
     * Adds form JS script code
     * 
     * @param AjaxRequestBuilder|AAjaxRequest|string $code JS code
     */
    public function addScript(AjaxRequestBuilder|AAjaxRequest|string $code) {
        if($code instanceof AjaxRequestBuilder) {
            $code = $code->build();
        } else if($code instanceof AAjaxRequest) {
            $_code = $code->build();
            $code->checkChecks();
            $code = $_code;
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
     * Renders the form elements only to HTML code - without the <form> tag
     */
    public function renderElementsOnly(): string {
        $code = '';

        foreach($this->elements as $name => $element) {
            $row = $this->buildElement($name, $element);

            $code .= $row->render();
        }

        return $code;
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

        if($this->hasFile) {
            $form->setFileUpload();
        }

        // APPLIES REDUCER
        if($this->reducer !== null && !$this->httpRequest->isAjax) {
            if(!$this->overrideReducerOnStartup && !$this->callAfterSubmitReducer) {
                $stateList = $this->getStateList();
                $this->reducer->applyOnStartupReducer($stateList);
                $this->applyStateList($stateList);
            }

            if($this->callAfterSubmitReducer) {
                $stateList = $this->getStateList();
                $this->reducer->applyAfterSubmitOnOpenReducer($stateList);
                $this->applyStateList($stateList);
            }
        }

        // BUILDS ELEMENTS TO ROWS
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
                if(in_array($k, ['page', 'action', 'do', 'state', 'elements'])) continue;

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
            'value',
            'min',
            'max'
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
                if(in_array($attr, ['value', 'min', 'max'])) {
                    $code .= 'if(' . $name . '_' . $attr . ' === undefined || ' . $name . '_' . $attr . ' === false || ' . $name . '_' . $attr . ' === "") { ' . $name . '_' . $attr . ' = "null"; }';
                    continue;
                }

                $code .= 'if(' . $name . '_' . $attr . ' === undefined || ' . $name . '_' . $attr . ' === false || ' . $name . '_' . $attr . ' === "") { ' . $name . '_' . $attr . ' = false; }';
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

        $this->addScript($code);

        $data = [];
        $args = [];

        foreach($this->httpRequest->query as $k => $v) {
            if(array_key_exists($k, $this->action)) continue;
            if(in_array($k, ['page', 'action', 'do'])) continue;

            $data[$k] = '_' . $k;
            $args[] = '_' . $k;
        }

        $data['state'] = '_state';
        $args[] = '_state';

        foreach(array_keys($this->elements) as $name) {
            if($name == 'btn_submit') continue;
            $data['elements'][] = $name;
        }

        foreach($this->action as $k => $v) {
            if(in_array($k, ['page', 'action', 'do'])) continue;

            $data[$k] = $v;
        }

        $par = new PostAjaxRequest($this->httpRequest);

        $par->setComponentUrl($this, 'onChange')
            ->setData($data);

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('form')
            ->setJsonResponseObjectName('form');
        
        $par->addOnFinishOperation($updateOperation);

        $customOperation = new CustomOperation();
        $customOperation->addCode('_state = getFormState();');
        
        $par->addBeforeStartOperation($customOperation);

        foreach($args as $arg) {
            $par->addArgument($arg);
        }

        $this->addScript($par);

        $___args = [];

        foreach($this->action as $k => $v) {
            if(in_array($k, ['page', 'action', 'do'])) continue;

            $___args[] = '\'' . $v . '\'';
        }

        $__args[] = '\'\'';

        $this->addScript('function ' . $this->componentName . '_onChange() { ' . $par->getFunctionName() . '(' . implode($___args) . '); }');

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

        if($label !== null && $element instanceof AInteractableElement) {
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
     * Adds easy security verification code check
     * 
     * @param string $name Element name
     * @param ?string &$hash Already generated hash or implicitly generated hash
     */
    public function addSecurityVerificationCodeCheck(string $name, ?string &$hash) {
        if($hash === null) {
            $hash = HashManager::createHash(8);
        }

        $labelText = 'Enter this verification code below: <b>' . $hash . '</b>';

        $this->addLabel('lbl_' . HashManager::createHash(4, false), $labelText);
        $this->addTextInput($name, 'Verification code:')
            ->setRequired();
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
     * Adds time input
     * 
     * @param string $name Element name
     * @param ?string $label Labet text or null
     * @return TimeInput TimeInput instance
     */
    public function addTimeInput(string $name, ?string $label = null) {
        $ti = new TimeInput($name);

        $this->elements[$name] = &$ti;

        $this->processLabel($name, $label);

        return $ti;
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
     * Adds file input
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     */
    public function addFileInput(string $name, ?string $label = null): FileInput {
        $fi = new FileInput($name);

        $this->elements[$name] = &$fi;

        $this->processLabel($name, $label);

        $this->hasFile = true;

        return $fi;
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
     * Adds form user select with all users
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @param ?string $containerId Container ID or null (if no container ID is entered, no users are displayed)
     */
    public function addUserSelect(string $name, ?string $label = null, ?string $containerId = null) {
        $s = new Select($name);

        $this->elements[$name] = &$s;

        $this->processLabel($name, $label);

        // ADD USERS
        $users = [];

        $getUserJson = function(string $userId) {
            return [
                'operation' => 'query',
                'name' => 'getUsers',
                'definition' => [
                    'users' => [
                        'get' => [
                            'cols' => [
                                'userId',
                                'fullname'
                            ],
                            'conditions' => [
                                [
                                    'col' => 'userId',
                                    'value' => $userId,
                                    'type' => 'eq'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        };

        if($containerId !== null) {
            $container = $this->app->containerManager->getContainerById($containerId);

            $groupUsers = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');

            foreach($groupUsers as $userId) {
                $user = json_decode($this->app->peeql->execute(json_encode($getUserJson($userId))), true)['data'];

                foreach($user as $row) {
                    $users[] = [
                        'value' => $row['userId'],
                        'text' => $row['fullname']
                    ];
                }
            }

            $s->addRawOptions($users);
        }

        return $s;
    }

    /**
     * Adds user select search
     * 
     * @param string $name Element name
     * @param ?string $label Label text or null
     * @param ?string $containerId Container ID or null (if no container ID is entered no users are displayed)
     */
    public function addUserSelectSearch(string $name, ?string $label = null, ?string $containerId = null) {
        $this->addTextInput('userSeach', 'Fullname:');

        $btn = $this->addButton('Search users');

        if($containerId !== null) {
            $btn->setOnClick('searchUsersSubmit()');

            $arb = new AjaxRequestBuilder();

            $arb->setMethod('POST')
                ->setComponentAction($this->presenter, $this->componentName . '-searchUsers')
                ->setHeader(['query' => '_query', 'containerId' => '_containerId'])
                ->setFunctionName('searchUsers')
                ->setFunctionArguments(['_query', '_containerId'])
                ->updateHTMLElement($name, 'data');

            $this->addScript($arb);

            $this->addScript('
                function searchUsersSubmit() {
                    const query = $("#userSearch").val();
                    const containerId = "' . $containerId . '";

                    searchUsers(query, containerId);
                }
            ');
        }

        $s = new Select($name);

        $this->elements[$name] = &$s;

        $this->processLabel($name, $label);

        return $s;
    }

    public function addPresenterSelectSearch(string $actionName, array $params, string $name, string $searchByLabel, string $label) {
        $this->addTextInput($name . 'Search', $searchByLabel);

        $btn = $this->addButton('Search');
        $btn->setOnClick('search' . ucfirst($name) . 'Submit()');

        $arb = new AjaxRequestBuilder();
        
        $arb->setMethod('POST')
            ->setAction($this->presenter, $actionName, $params)
            ->setHeader(['query' => '_query'])
            ->setFunctionName('search' . ucfirst($name))
            ->setFunctionArguments(['_query'])
            ->updateHTMLElement($name, 'data');

        $this->addScript($arb);

        $this->addScript('
            function search' . ucfirst($name) . 'Submit() {
                const query = $("#' . $name . 'Search").val();

                search' . ucfirst($name) . '(query);
            }
        ');

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

    /**
     * Handles user select search subform
     */
    public function actionSearchUsers() {
        $query = $this->httpRequest->get('query');
        $containerId = $this->httpRequest->get('containerId');

        $container = $this->app->containerManager->getContainerById($containerId);

        $groupUsers = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');

        $users = [];

        $getUserJson = function() use ($query) {
            return [
                'operation' => 'query',
                'name' => 'getUsers',
                'definition' => [
                    'users' => [
                        'get' => [
                            'cols' => [
                                'userId',
                                'fullname'
                            ],
                            'conditions' => [
                                [
                                    'col' => 'fullname',
                                    'value' => $query,
                                    'type' => 'like'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        };

        $user = json_decode($this->app->peeql->execute(json_encode($getUserJson($query))), true);

        foreach($user as $row) {
            if(!in_array($row['userId'], $groupUsers)) continue;

            $users[] = '<option value="' . $row['userId'] . '">' . $row['fullname'] . '</option>';
        }

        return new JsonResponse(['data' => implode('', $users)]);
    }
}

?>