<?php

namespace App\UI\ListBuilder;

/**
 * Class that represents a column in list
 * 
 * @author Lukas Velek
 */
class ListColumn {
    /**
     * Methods are called with parameters: ArrayRow $row, ListRow $_row, ListCell $cell, mixed $value
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