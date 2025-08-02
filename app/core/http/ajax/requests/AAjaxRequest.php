<?php

namespace App\Core\Http\Ajax\Requests;

use App\Core\HashManager;
use App\Core\Http\Ajax\Operations\IAjaxOperation;
use App\Core\Http\HttpRequest;
use App\Core\Router;
use App\Exceptions\GeneralException;
use App\UI\AComponent;

/**
 * Common predecessor for all AJAX requests
 * 
 * @author Lukas Velek
 */
abstract class AAjaxRequest implements IAjaxRequest {
    protected const RESULT_TYPE_JSON = 'json';
    protected const RESULT_TYPE_TEXT = 'text';

    private HttpRequest $request;
    private bool $buildCheck;
    private string $functionName;

    protected array $url;
    /**
     * @var array<int, IAjaxOperation> $onFinishOperations
     */
    protected array $onFinishOperations;
    /**
     * @var array<int, IAjaxOperations> $beforeStartOperations
     */
    protected array $beforeStartOperations;
    protected array $arguments;
    protected array $data;
    protected string $resultType;
    private array $urlParameters;

    /**
     * Abstract class constructor
     */
    protected function __construct(HttpRequest $request) {
        $this->request = $request;

        $this->url = [];
        $this->onFinishOperations = [];
        $this->beforeStartOperations = [];
        $this->arguments = [];
        $this->buildCheck = false;
        $this->data = [];
        $this->resultType = self::RESULT_TYPE_JSON;
        $this->urlParameters = [];

        $this->functionName = $this->generateFunctionName();
    }

    /**
     * Adds a URL parameter
     * 
     * @param string $key Key
     * @param mixed $value Value
     */
    public function addUrlParameter(string $key, mixed $value) {
        $this->urlParameters[$key] = $value;
    }

    /**
     * Sets the result type to JSON
     */
    public function setResultTypeJson() {
        $this->resultType = self::RESULT_TYPE_JSON;
    }

    /**
     * Sets the result type to text
     */
    public function setResultTypeText() {
        $this->resultType = self::RESULT_TYPE_TEXT;
    }

    /**
     * Adds an operation that is performed after the AJAX request has been finished
     * 
     * @param IAjaxOperation $operation Operation
     */
    public function addOnFinishOperation(IAjaxOperation $operation): static {
        $this->onFinishOperations[] = $operation;
        return $this;
    }

    /**
     * Adds an operation that is performed before the AJAX request is called
     * 
     * @param IAjaxOperation $operation Operation
     */
    public function addBeforeStartOperation(IAjaxOperation $operation): static {
        $this->beforeStartOperations[] = $operation;
        return $this;
    }

    /**
     * Generates JS function name
     */
    private function generateFunctionName(): string {
        return '_' . HashManager::createHash(8, false);
    }

    /**
     * Returns the JS function name
     */
    public function getFunctionName(): string {
        return $this->functionName;
    }

    /**
     * Creates the JS function skeleton
     * 
     * @param array $payload Header or data payload
     */
    protected function internalBuild(string $ajaxRequestCode): string {
        $this->buildCheck = true;

        $code = 'async function ' . $this->getFunctionName() . '(' . implode(', ', $this->arguments) . ') {';
        $code .= $ajaxRequestCode;
        $code .= ' }';

        return $code;
    }

    /**
     * Converts URL array to string
     */
    protected function processUrl(): string {
        $url = array_merge($this->url, $this->urlParameters);

        return Router::generateUrl($url);
    }

    /**
     * Adds an argument
     * 
     * This argument will have to be passed in the JS function's constructor
     * 
     * @param string $argument Argument name
     */
    public function addArgument(string $argument): static {
        $this->arguments[] = $argument;
        return $this;
    }

    /**
     * Adds multiple arguments
     * 
     * These arguments will have to be passed in the JS function's constructor
     * 
     * @param array $arguments Arguments
     */
    public function addArguments(array $arguments): static {
        $this->arguments = array_merge($this->arguments, $arguments);
        return $this;
    }

    /**
     * Sets the request's URL
     * 
     * @param array $url URL
     */
    public function setUrl(array $url): static {
        $this->url = $url;
        return $this;
    }

    /**
     * Sets the request's URL for component action call
     * 
     * @param AComponent $component Component instance that is going to be called
     * @param string $actionName Action name
     */
    public function setComponentUrl(AComponent $component, string $actionName): static {
        $this->url = [
            'page' => $this->request->get('page'),
            'action' => $this->request->get('action'),
            'do' => $component->getComponentName() . '-' . $actionName
        ];
        return $this;
    }

    /**
     * Checks all checks
     */
    public function checkChecks() {
        if(!$this->buildCheck) {
            throw new GeneralException('Internal build function has not been called!');
        }
    }

    /**
     * Creates a call JS code
     * 
     * @param $params Optional parameters
     */
    public function call(...$params): string {
        if(count($params) < count($this->arguments)) {
            throw new GeneralException('Number of passed parameters in call() method does not match the number of expected arguments.');
        }

        $finalParams = [];
        foreach($params as $param) {
            if(is_string($param) && !in_array($param, $this->arguments)) {
                $finalParams[] = '"' . $param . '"';
            } else if(is_bool($param)) {
                $finalParams[] = $param ? '1' : '0';
            } else {
                $finalParams[] = $param;
            }
        }

        return $this->getFunctionName() . '(' . implode(', ', $finalParams) . ')';
    }

    /**
     * Processes data payload
     */
    protected function processData(): string {
        $json = json_encode($this->data);

        foreach($this->arguments as $argument) {
            $json = str_replace('"' . $argument . '"', $argument, $json);
        }

        return $json;
    }

    /**
     * Sets the request's data payload
     * 
     * @param array $data Data
     */
    public function setData(array $data): static {
        $this->data = $data;
        return $this;
    }
}

?>