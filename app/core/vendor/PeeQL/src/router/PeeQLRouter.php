<?php

namespace PeeQL\Router;

use Exception;

/**
 * PeeQLRouter contains route definitions to database query handlers
 * 
 * @author Lukas Velek
 */
class PeeQLRouter {
    private array $routes;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->routes = [];
    }

    /**
     * Adds a route with a handler instance
     * 
     * @param string $name Route name
     * @param object $object Handler
     */
    public function addObjectRoute(string $name, object $object) {
        $this->routes[$name] = $object;
    }

    /**
     * Adds a route without a handler instance
     * 
     * @param string $name Route name
     * @param string $className Handler class name
     * @param array $params Handler class constructor parameters
     */
    public function addRoute(string $name, string $className, array $params = []) {
        try {
            $object = new $className(...$params);
        } catch(Exception $e) {
            throw new Exception(sprintf('Could not create an instance of \'%s\'.', $className), 9999, $e);
        }

        $this->addObjectRoute($name, $object);
    }

    /**
     * Returns the route
     * 
     * @param string $name Name
     */
    public function route(string $name): object {
        if(!array_key_exists($name, $this->routes)) {
            throw new Exception(sprintf('No route is defined for \'%s\'.', $name), 9999);
        }
        
        return $this->routes[$name];
    }
}

?>