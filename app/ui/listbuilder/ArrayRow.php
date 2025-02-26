<?php

namespace App\UI\ListBuilder;

/**
 * Class representing a row in an array
 * 
 * @author Lukas Velek
 */
class ArrayRow {
    private array $data;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->data = [];
    }

    public function __set(string $key, mixed $value) {
        $this->data[$key] = $value;
    }

    public function __get(string $key) {
        if(array_key_exists($key, $this->data)) {
            return $this->data[$key];
        } else {
            return null;
        }
    }

    /**
     * Returns all keys
     */
    public function getKeys() {
        return array_keys($this->data);
    }

    /**
     * Creates an ArrayRow instance from array data
     */
    public static function createFromArrayData(array $data) {
        $obj = new self();
        foreach($data as $key => $value) {
            $obj->$key = $value;
        }

        return $obj;
    }
}

?>