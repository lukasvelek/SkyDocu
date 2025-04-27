<?php

namespace App\UI\FormBuilder2;

use App\Core\Application;
use App\Core\Container;
use App\Core\Http\HttpRequest;
use App\Exceptions\GeneralException;

/**
 * Common class for form reducers
 * 
 * @author Lukas Velek
 */
abstract class ABaseFormReducer implements IFormReducer {
    protected Application $app;
    protected HttpRequest $request;
    protected ?Container $container;

    /**
     * Class constructor
     * 
     * @param Application $app Application instance
     * @param HttpRequest $request Http request
     */
    public function __construct(Application $app, HttpRequest $request) {
        $this->app = $app;
        $this->request = $request;

        $this->container = null;
    }

    /**
     * Sets container ID
     * 
     * @param ?string $containerId Container ID
     */
    public function setContainerId(?string $containerId) {
        if($containerId !== null) {
            $this->container = new Container($this->app, $containerId);
        }
    }

    /**
     * Checks if container variable is null and throws an exception
     * 
     * @throws GeneralException
     */
    protected function throwContainerIsNull() {
        if($this->container === null) {
            throw new GeneralException('Container is not set but it is required.');
        }
    }
}

?>