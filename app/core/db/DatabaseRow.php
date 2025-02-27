<?php

namespace App\Core\DB;

/**
 * Class representing a single row from database query
 * 
 * @author Lukas Velek
 */
class DatabaseRow {
    private array $values;
    private mixed $originalRow;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->values = [];
        $this->originalRow = null;
    }
    
    /**
     * Sets the value
     * 
     * @param mixed $key Key
     * @param mixed $value Value
     */
    public function __set(mixed $key, mixed $value) {
        $this->values[$key] = $value;
    }

    /**
     * Returns the value
     * 
     * @param mixed $key Key
     * @return mixed Value
     */
    public function __get(mixed $key) {
        if(array_key_exists($key, $this->values)) {
            return $this->values[$key];
        } else {
            return null;
        }
    }

    /**
     * Returns all keys
     * 
     * @return array All keys
     */
    public function getKeys() {
        return array_keys($this->values);
    }

    /**
     * Returns the original raw database row
     */
    public function getOriginalRow() {
        return $this->originalRow;
    }

    /**
     * Creates a DatabaseRow instance from mysqli_result $row
     * 
     * @param mixed $row mysqli_result row
     */
    public static function createFromDbRow($row) {
        $obj = new self();

        $obj->originalRow = $row;

        foreach($row as $k => $v) {
            $obj->$k = $v;
        }

        return $obj;
    }
}

?>