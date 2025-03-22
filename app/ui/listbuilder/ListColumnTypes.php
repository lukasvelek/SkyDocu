<?php

namespace App\UI\ListBuilder;

/**
 * List column types
 * 
 * @author Lukas Velek
 */
class ListColumnTypes {
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
     * Constant column
     */
    public const COL_TYPE_CONST = 'const';
}

?>