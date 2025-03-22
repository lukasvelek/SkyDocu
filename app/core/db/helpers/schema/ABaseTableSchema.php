<?php

namespace App\Core\DB\Helpers\Schema;

/**
 * Common class for all table schema manipulation classes
 * 
 * @author Lukas Velek
 */
abstract class ABaseTableSchema {
    protected string $name;

    /**
     * Class constructor
     * 
     * @param string $name Table name
     */
    public function __construct(string $name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns an array of SQL queries for table schema manipulation
     */
    public abstract function getSQL(): array;
}

?>