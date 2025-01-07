<?php

namespace App\Core\Http;

/**
 * FormRequest represents a form request. It contains data received from sent form.
 * 
 * @author Lukas Velek
 */
class FormRequest {
    private array $data;
    private array $keys;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->data = [];
        $this->keys = [];
    }

    public function __set(string $key, mixed $value) {
        $this->data[$key] = $value;
        $this->keys[] = $key;
    }

    public function __get(string $key) {
        if(in_array($key, $this->keys)) {
            return $this->data[$key];
        } else {
            return null;
        }
    }

    /**
     * Returns all keys
     * 
     * @return array<int, string> Keys
     */
    public function getKeys() {
        return $this->keys;
    }
    
    /**
     * Returns all data
     * 
     * @return array<string, mixed> Data
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Creates a FormRequest instance from sanitized $_POST values
     * 
     * @param array $values Sanitized $_POST values
     */
    public static function createFromPostData(array $values) {
        $obj = new self();

        foreach($values as $k => $v) {
            $obj->$k = $v;
        }

        return $obj;
    }
}

?>