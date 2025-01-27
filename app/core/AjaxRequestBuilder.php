<?php

namespace App\Core;

use App\Modules\APresenter;
use App\UI\LinkBuilder;

/**
 * Class that helps developer to create an Ajax request easily.
 * 
 * @author Lukas Velek
 */
class AjaxRequestBuilder {
    private ?string $url;
    private array $headerParams;
    private array $whenDoneOperations;
    private ?string $functionName;
    private ?string $method;
    private array $functionArgs;
    private array $beforeAjaxOperations;
    private array $customArgs;
    private array $elements;
    private bool $useLoadingAnimation;
    private bool $isComponent;
    private array $data;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->url = null;
        $this->headerParams = [];
        $this->whenDoneOperations = [];
        $this->functionName = null;
        $this->method = null;
        $this->functionArgs = [];
        $this->beforeAjaxOperations = [];
        $this->customArgs = [];
        $this->elements = [];
        $this->useLoadingAnimation = true;
        $this->isComponent = false;
        $this->data = [];

        return $this;
    }

    /**
     * Sets POST data payload
     * 
     * @param array $data
     */
    public function setData(array $data): static {
        $this->data = $data;
        return $this;
    }

    /**
     * Sets whether the call is coming from a component
     * 
     * @param bool $component True if the call is coming from a component or false if not
     */
    public function setComponent(bool $component = true): static {
        $this->isComponent = $component;
        return $this;
    }
    
    /**
     * Disables loading animation when ajax request is being processed
     */
    public function disableLoadingAnimation(): static {
        $this->useLoadingAnimation = false;

        return $this;
    }

    /**
     * Adds an operation that is executed before ajax request is processed
     * 
     * @param string $code JS code
     */
    public function addBeforeAjaxOperation(string $code): static {
        $this->beforeAjaxOperations[] = $code;

        return $this;
    }

    /**
     * Adds custom argument to the header section of ajax request
     * 
     * @param string $argName Argument name
     */
    public function addCustomArg(string $argName): static {
        $this->customArgs[] = $argName;

        return $this;
    }

    /**
     * Sets the ajax request handler that is located in the given presenter
     * 
     * @param APresenter $presenter Presenter
     * @param string $actionName Handler action
     * @param array $params Additional URL parameters
     */
    public function setAction(APresenter $presenter, string $actionName, array $params = []): static {
        $module = $presenter->moduleName;
        $presenter = $presenter->getCleanName();

        $url = array_merge(['page' => $module . ':' . $presenter, 'action' => $actionName], $params);

        $this->url = $this->composeURLFromArray($url);

        return $this;
    }

    /**
     * Sets the ajax request handler that is located in the component class
     * 
     * @param APresenter $presenter Presenter
     * @param string $componentActionName Component handler action
     * @param array $params Additional URL parameters
     */
    public function setComponentAction(APresenter $presenter, string $componentActionName, array $params = []): static {
        $module = $presenter->moduleName;
        $action = $presenter->getAction();
        $presenter = $presenter->getCleanName();

        $url = array_merge(['page' => $module . ':' . $presenter, 'action' => $action, 'do' => $componentActionName], $params);

        $this->url = $this->composeURLFromArray($url);
        $this->isComponent = true;

        return $this;
    }

    /**
     * Sets custom ajax request handler URL
     * 
     * @param array $url Handler URL params
     */
    public function setURL(array $url): static {
        $this->url = $this->composeURLFromArray($url);

        return $this;
    }

    /**
     * Creates a string URL from array URL params
     * 
     * @param array $url URL params
     * @return string URL string
     */
    private function composeURLFromArray(array $url) {
        return LinkBuilder::convertUrlArrayToString($url);
    }

    /**
     * Sets the header arguments. Array keys are keys of the arguments and array values are the values of the arguments.
     * 
     * @param array $params Header arguments
     */
    public function setHeader(array $params): static {
        $this->headerParams = $params;

        return $this;
    }

    /**
     * Adds operation that is executed after the ajax request is processed and response is received
     * 
     * @param string $code JS code
     */
    public function addWhenDoneOperation(string $code): static {
        $this->whenDoneOperations[] = $code;

        return $this;
    }

    /**
     * Updates HTML element content to the result given in the JSON
     * 
     * @param string $htmlElementId ID of the HTML element
     * @param string $jsonResultName JSON object parameter name
     * @param null|bool $append True if the JSON result should be appended or false if it should overwrite the currrent content or null if the JSON result should be prepended
     */
    public function updateHTMLElement(string $htmlElementId, string $jsonResultName, null|bool $append = false): static {
        if(!$append) {
            $this->elements[] = $htmlElementId;
        }

        $action = 'html';
        if($append) {
            $action = 'append';
        } else if($append === null) {
            $action = 'prepend';
        }

        $this->addWhenDoneOperation('$("#' . $htmlElementId . '").' . $action . '(obj.' . $jsonResultName . ');');

        return $this;
    }

    /**
     * Updates HTML element content to the result given in the JSON. HTML element in this case does not have to be ID.
     * 
     * @param string $htmlElement HTML element
     * @param string $jsonResultName JSON object parameter name
     * @param bool $append True if the JSON result should be appended or false if it should overwrite the current content
     */
    public function updateHTMLElementRaw(string $htmlElement, string $jsonResultName, bool $append = false): static {
        if(!$append) {
            $this->elements[] = $htmlElement;
        }
        $this->addWhenDoneOperation('$(' . $htmlElement . ').' . ($append ? 'append' : 'html') . '(obj.' . $jsonResultName . ');');

        return $this;
    }

    /**
     * Hides given HTML element
     * 
     * @param string $htmlElement HTML element
     */
    public function hideHTMLElementRaw(string $htmlElement): static {
        $this->addWhenDoneOperation('$(' . $htmlElement . ').hide();');

        return $this;
    }

    /**
     * Hides given HTML element that is found by given element ID
     * 
     * @param string $htmlElementId ID of the HTML element
     */
    public function hideHTMLElement(string $htmlElementId): static {
        $this->hideHTMLElementRaw('"#' . $htmlElementId . '"');

        return $this;
    }

    /**
     * Sets the JS function name
     * 
     * @param string $name JS function name
     */
    public function setFunctionName(string $name): static {
        $this->functionName = $name;

        return $this;
    }

    /**
     * Sets the JS function arguments
     * 
     * @param array $args JS function arguments
     */
    public function setFunctionArguments(array $args): static {
        $this->functionArgs = $args;

        return $this;
    }

    /**
     * Sets the AJAX request method (POST/GET)
     * 
     * @param string $method Method
     */
    public function setMethod(string $method = 'GET'): static {
        $this->method = $method;

        return $this;
    }

    /**
     * Checks if all needed parameters are not empty or null and then build final JS code that is callable.
     * 
     * @return string JS code
     */
    public function build() {
        if(!$this->checkParameters()) {
            return null;
        }
        
        if($this->useLoadingAnimation) {
            $this->createLoadingAnimation();
        }

        $headParams = $this->processHeadParams();

        $code = [];

        $code[] = 'async function ' . $this->functionName . '(' . implode(', ', $this->functionArgs) . ') {';

        if(!empty($this->beforeAjaxOperations)) {
            foreach($this->beforeAjaxOperations as $bao) {
                $code[] = $bao;
            }
        }

        if(strtoupper($this->method) == 'GET') {
            $code[] = '$.get(';
            $code[] = '"' . $this->url . '",';
            $code[] = $headParams;
            $code[] = ')';
        } else if (strtoupper($this->method) == 'POST') {
            $code[] = '$.post(';
            $code[] = '"' . $this->url . '",';

            if(!empty($this->data)) {
                $code[] = $this->processData();
            } else {
                $code[] = $headParams;
            }

            $code[] = ')';
        }
        
        if(!empty($this->whenDoneOperations)) {
            $code[] = '.done(function(data){';
            $code[] = 'try {';
            $code[] = 'const obj = JSON.parse(data);';

            $code[] = 'if(obj.error && obj.error == 1) {';
            $code[] = 'if(obj.errorMsg) { alert(obj.errorMsg); }';
            $code[] = '} else {';

            foreach($this->whenDoneOperations as $wdo) {
                $code[] = $wdo;
            }

            $code[] = '}';

            $code[] = '} catch (error) {';
            $code[] = 'alert("Could not load data. See console for more information.");';
            $code[] = 'console.log(error);';
            $code[] = '}';

            $code[] = '});';
        }

        $code[] = '}';

        return implode('', $code);
    }

    /**
     * Creates AJAX request head parameters
     * 
     * @return string JSON encoded parameters
     */
    private function processHeadParams() {
        if(!array_key_exists('isAjax', $this->headerParams)) {
            if($this->method == 'GET') {
                $this->headerParams['isAjax'] = 1;
            } else {
                $this->url .= '&isAjax=1';
            }
        }

        if($this->isComponent) {
            if($this->method == 'GET') {
                $this->headerParams['isComponent'] = 1;
            } else {
                $this->url .= '&isComponent=1';
            }
        }

        $json = json_encode($this->headerParams);

        foreach($this->functionArgs as $fa) {
            if(str_contains($json, '"' . $fa . '"')) {
                $json = str_replace('"'. $fa . '"', $fa, $json);
            }
        }

        foreach($this->customArgs as $ca) {
            if(str_contains($json, '"' . $ca . '"')) {
                $json = str_replace('"' . $ca . '"', $ca, $json);
            }
        }

        return $json;
    }

    /**
     * Checks if all required parameters are filled in
     * 
     * @return bool True if the check was successful or false if not
     */
    private function checkParameters() {
        if($this->functionName === null) {
            return false;
        }
        if($this->method === null || (strtoupper($this->method) != 'GET' && strtoupper($this->method) != 'POST')) {
            return false;
        }
        if($this->url === null) {
            return false;
        }

        return true;
    }

    /**
     * Creates a loading animation and displays it to the user
     */
    private function createLoadingAnimation() {
        foreach($this->elements as $element) {
            if($element == 'grid-content' || $element == 'grid') {
                $code = '
                    $("#' . $element . '").html(\'<div id="center"><img src="resources/loading.gif" width="64"><br>Loading...</div>\');
                ';

                $this->addBeforeAjaxOperation($code);
            }
        }

        $this->addBeforeAjaxOperation('await sleep(100);');
    }

    /**
     * Explicitly enables loading animation
     * 
     * @param string $element Element name
     * @param bool $isRaw True if the element name is raw or false if not
     */
    public function enableLoadingAnimation(string $element, bool $isRaw = false): static {
        $code = '
            $(' . ($isRaw ? '' : '"#') . $element . ($isRaw ? '' : '"') . ').html(\'<div id="center"><img src="resources/loading.gif" width="64"><br>Loading...</div>\');
        ';

        $this->addBeforeAjaxOperation($code);

        return $this;
    }

    /**
     * Processes data and if needed implements JS function arguments into the JSON
     * 
     * @return string Processed data formatted to JSON
     */
    private function processData() {
        $toReplace = [];
        foreach($this->functionArgs as $k) {
            if(in_array($k, $this->data)) {
                $toReplace[] = $k;
            }
        }

        $data = json_encode($this->data);
        foreach($toReplace as $k) {
            $data = str_replace('"' . $k . '"', $k, $data);
        }

        return $data;
    }
}

?>