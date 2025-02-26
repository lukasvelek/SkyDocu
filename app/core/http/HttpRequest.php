<?php

namespace App\Core\Http;

use App\Entities\UserEntity;

/**
 * HttpRequest represents a single HTTP request. It contains query parameters and a boolean that indicates whether it is a AJAX call or not.
 * 
 * @author Lukas Velek
 */
class HttpRequest {
    /**
     * Query parameters
     * 
     * @var array<string, mixed> $query
     */
    public array $query;

    /**
     * POST parameters
     * 
     * @var array<string, mixed> $post
     */
    public array $post;

    /**
     * Is the call AJAX?
     * 
     * @var bool $isAjax
     */
    public bool $isAjax;

    /**
     * Custom parameters
     * 
     * @var array<string, mixed> $params
     */
    public array $params;

    /**
     * Current user or null
     * 
     * @var ?UserEntity $currentUser
     */
    public ?UserEntity $currentUser;

    /**
     * Request method
     * 
     * @var ?string $method
     */
    public ?string $method;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->query = [];
        $this->post = [];
        $this->isAjax = false;
        $this->params = [];
        $this->currentUser = null;
        $this->method = null;
    }

    /**
     * Returns the given key from the query. If the key is not found, null is then returned instead.
     * 
     * @param string $key Searched key
     * @return mixed Data from query or null
     */
    public function query(string $key) {
        if(array_key_exists($key, $this->query)) {
            return $this->query[$key];
        } else {
            return null;
        }
    }

    /**
     * Returns the given key from the POST. If the key is not found, null is then returned instead.
     * 
     * @param string $key Searched key
     * @return mixed Data from POST or null
     */
    public function post(string $key) {
        if(array_key_exists($key, $this->post)) {
            return $this->post[$key];
        } else {
            return null;
        }
    }

    /**
     * Return the given key from either the query or the POST. If the key is not found in any of them, null is then returned instead.
     * 
     * @param string $key Searched key
     * @return mixed Data from query or POST or null
     */
    public function get(string $key): mixed {
        $result = $this->query($key) ?? $this->post($key);
        return $result;
    }
}

?>