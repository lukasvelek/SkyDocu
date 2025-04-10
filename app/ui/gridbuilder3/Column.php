<?php

namespace App\UI\GridBuilder3;

/**
 * Class that represents a column in grid table
 * 
 * @author Lukas Velek
 */
class Column {
    /**
     * Methods are called with parameters: array $row, mixed $value
     */
    public array $onRenderColumn;

    private string $name;

    /**
     * Class constructor
     * 
     * @param string $name Column name
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Returns the column name
     */
    public function getName() {
        return $this->name;
    }
}

?>