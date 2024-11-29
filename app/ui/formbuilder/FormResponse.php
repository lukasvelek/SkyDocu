<?php

namespace App\UI\FormBuilder;

use App\Core\HashManager;
use App\Core\Http\HttpRequest;

/**
 * FormResponse class is used to handle data of a submitted form
 * 
 * @author Lukas Velek
 */
class FormResponse {
    private array $__keys;
    private HttpRequest $httpRequest;

    /**
     * Class constructor
     * 
     * @param HttpRequest $httpRequest HttpRequest instance
     */
    public function __construct(HttpRequest $httpRequest) {
        $this->httpRequest = $httpRequest;

        $this->__keys = [];
    }

    public function __set(string $key, mixed $value) {
        $this->$key = $value;
        $this->__keys[] = $key;
    }

    public function __get(string $key) {
        return $key;
    }

    /**
     * Creates FormResponse instance from $_POST data
     * 
     * @param array $postData $_POST data
     * @param HttpRequest $httpRequest instance
     * @return self
     */
    public static function createFormResponseFromPostData(array $postData, HttpRequest $httpRequest) {
        $fr = new self($httpRequest);

        foreach($postData as $k => $v) {
            $fr->$k = $v;
        }

        return $fr;
    }

    /**
     * Checks if the two given parameters are equal
     * 
     * @param mixed $value1
     * @param mixed $value2
     * @return bool
     */
    public function evalBool(mixed $value1, mixed $value2) {
        return $value1 == $value2;
    }

    /**
     * Returns hashed password
     * 
     * @param string $name $_POST key
     * @return ?string Hashed password or false if not found
     */
    public function getHashedPassword(string $name = 'password') {
        if(isset($this->$name)) {
            return HashManager::hashPassword($this->$name);
        } else {
            return null;
        }
    }

    /**
     * Returns all values
     * 
     * @return array
     */
    public function getAllValues() {
        $data = [];
        foreach($this->__keys as $key) {
            $data[$key] = $this->$key;
        }

        return $data;
    }
}

?>