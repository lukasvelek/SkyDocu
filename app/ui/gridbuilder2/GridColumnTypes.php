<?php

namespace App\UI\GridBuilder2;

/**
 * Grid column types
 * 
 * @author Lukas Velek
 */
class GridColumnTypes {
    /**
     * General text column
     */
    public const COL_TYPE_TEXT = 'text';
    /**
     * Datetime column
     */
    public const COL_TYPE_DATETIME = 'datetime';
    /**
     * Boolean column
     */
    public const COL_TYPE_BOOLEAN = 'boolean';
    /**
     * User column - render user's fullname
     */
    public const COL_TYPE_USER = 'user';
}

?>