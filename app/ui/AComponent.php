<?php

namespace App\UI;

use App\Core\FileManager;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\CallbackExecutionException;
use App\Exceptions\GeneralException;
use App\Modules\AGUICore;
use App\Modules\TemplateObject;
use Exception;

/**
 * Common class for interactive components
 * 
 * @author Lukas Velek
 * @version 1.0
 */
abstract class AComponent extends AGUICore implements IRenderable {
    public HttpRequest $httpRequest;
    protected string $componentName = 'Component';
    private bool $startupCheck = false;
    private bool $prerenderCheck = false;

    /**
     * Abstract class constructor
     * 
     * @param HttpRequest $httpRequest HttpRequest instance
     */
    protected function __construct(HttpRequest $httpRequest) {
        $this->httpRequest = $httpRequest;
    }

    /**
     * Sets the component name
     * 
     * @param string $componentName Component name
     */
    public function setComponentName(string $componentName) {
        $this->componentName = $componentName;
    }

    /**
     * Returns the component name
     */
    public function getComponentName(): string {
        return $this->componentName;
    }

    /**
     * Initial component configuration
     */
    public function startup() {
        $this->startupCheck = true;
    }

    /**
     * Actions called before render() is called
     */
    public function prerender() {
        $this->prerenderCheck = true;
    }

    /**
     * Creates an instance of component from other component
     * 
     * @param AComponent $component Other component
     */
    abstract static function createFromComponent(AComponent $component);

    /**
     * Calls a method on $this
     * 
     * @param string $methodName Method name
     * @param array $args Method arguments
     * 
     * @return mixed Method's result
     */
    public function processMethod(string $methodName, array $args = []) {
        try {
            return $this->$methodName(...$args);
        } catch(AException|Exception $e) {
            throw new CallbackExecutionException($e, [$methodName], $e);
        }
    }

    /**
     * Loads content of file in given path and creates a new TemplateObject instance.
     * 
     * @param string $path Template content path
     * @return TemplateObject TemplateObject instance
     */
    protected function loadTemplateFromPath(string $path) {
        $content = FileManager::loadFile($path);

        return new TemplateObject($content);
    }

    /**
     * Checks if all component checks are checked
     */
    public function checkChecks() {
        if(!$this->startupCheck) {
            throw new GeneralException('Method \'' . AComponent::class . '::startup()\' has not been called (' . $this->componentName . ').', null, false);
        }
        if(!$this->prerenderCheck) {
            throw new GeneralException('Method \'' . AComponent::class . '::prerender()\' has not been called (' . $this->componentName . ').', null, false);
        }
    }

    /**
     * Executes a PeeQL JSON query and returns the result as an associative array
     * 
     * @param array $json JSON query
     * 
     * @throws GeneralException
     * @throws Exception
     */
    protected function executePeeQL(array $json): array {
        if(isset($this->app)) {
            return $this->app->peeql->execute($json);
        } else {
            throw new GeneralException('Application instance is not set.');
        }
    }
}

?>