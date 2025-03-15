<?php

namespace App\Core\DB\Helpers;

use App\Core\DB\Helpers\Schema\CreateTableSchema;
use App\Core\DB\Helpers\Schema\DropTableSchema;
use App\Core\DB\Helpers\Schema\UpdateTableSchema;

/**
 * TableSchema helps with creating a table schema during migrations
 * 
 * @author Lukas Velek
 */
class TableSchema {
    private array $tables = [];

    public function __construct() {}

    /**
     * Returns an instance of CreateTableSchema for table schema creation
     * 
     * @param string $name Table name
     */
    public function create(string $name): CreateTableSchema {
        $create = new CreateTableSchema($name);
        $this->tables[$name] = &$create;

        return $create;
    }

    /**
     * Returns an instance of DropTableSchema for table schema drop
     * 
     * @param string $name Table name
     */
    public function drop(string $name): DropTableSchema {
        $drop = new DropTableSchema($name);

        $this->tables[$name] = &$drop;

        return $drop;
    }

    /**
     * Returns an instance of UpdateTableSchema for table schema update
     * 
     * @param string $name Table name
     */
    public function update(string $name): UpdateTableSchema {
        $update = new UpdateTableSchema($name);

        $this->tables[$name] = &$update;

        return $update;
    }

    /**
     * Returns all defined table schemas or their updates
     */
    public function getTableSchemas(): array {
        return $this->tables;
    }
}

?>