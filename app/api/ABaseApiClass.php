<?php

namespace App\Api;

use App\Core\Application;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;

/**
 * Common class for all API controllers
 * 
 * @author Lukas Velek
 */
abstract class ABaseApiClass {
    protected Application $app;
    protected HttpRequest $request;

    /**
     * Class constructor
     * 
     * @param Application $app Application instance
     */
    public function __construct(Application $app) {
        $this->app = $app;

        $this->request = $app->getRequest();
    }

    /**
     * Processes the API request
     * 
     * @return JsonResponse
     */
    public abstract function run(): JsonResponse;
}

?>