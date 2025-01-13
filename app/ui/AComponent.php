<?php

namespace App\UI;

use App\Core\FileManager;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\CallbackExecutionException;
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
    protected HttpRequest $httpRequest;
    protected string $componentName = 'Component';

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
     * Initial component configuration
     */
    public function startup() {}

    /**
     * Actions called before render() is called
     */
    public function prerender() {}

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
}

?>