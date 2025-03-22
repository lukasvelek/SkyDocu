<?php

namespace App\Core\DB\Helpers\Seeding;

/**
 * Creates table seeding for given table
 * 
 * @author Lukas Velek
 */
class CreateTableSeeding {
    private string $name;
    private array $data = [];

    /**
     * Class constructor
     * 
     * @param string $name Table name
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Adds seed
     * 
     * Data array must be in this format:
     * [
     *  'column1' => 'value1',
     *  'column2' => 'value2',
     *  ...
     * ]
     * 
     * @param array $data Data
     */
    public function add(array $data): static {
        $this->data[] = $data;

        return $this;
    }

    /**
     * Returns an array of SQL queries
     */
    public function getSQL(): array {
        $sqls = [];

        foreach($this->data as $data) {
            $columns = array_keys($data);
            $_data = array_values($data);

            $sql = "INSERT INTO " . $this->name . " (" . implode(', ', $columns) . ") VALUES ('" . implode('\', \'', $_data) . "')";

            $sqls[] = $sql;
        }

        return $sqls;
    }
}

?>