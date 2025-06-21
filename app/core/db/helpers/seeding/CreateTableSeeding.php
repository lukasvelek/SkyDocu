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
    private array $deleteData = [];
    private bool $deleteAll = false;

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
     * Removes seed
     * 
     * Data array must be in this format:
     * [
     *  'column1' => 'value1'
     * ]
     * 
     * The delete SQL query has a condition that is: column1 = value1
     * 
     * @param array $data Data
     */
    public function delete(array $data): static {
        $this->deleteData[] = $data;

        return $this;
    }

    /**
     * Deletes all seeds
     * 
     * @param bool $deleteAll Delete all?
     */
    public function deleteAll(bool $deleteAll = true): static {
        $this->deleteAll = $deleteAll;

        return $this;
    }

    /**
     * Returns an array of SQL queries
     */
    public function getSQL(): array {
        $sqls = [];

        if($this->deleteAll === false) {
            foreach($this->data as $data) {
                $columns = array_keys($data);
                $_data = array_values($data);
    
                $sql = "INSERT INTO " . $this->name . " (" . implode(', ', $columns) . ") VALUES ('" . implode('\', \'', $_data) . "')";
    
                $sqls[] = $sql;
            }
    
            foreach($this->deleteData as $data) {
                foreach($data as $key => $value) {
                    $sql = "DELETE FROM " . $this->name . " WHERE $key = '$value'";
    
                    $sql[] = $sql;
                }
            }
        } else {
            $sql = "DELETE FROM " . $this->name;
        }

        return $sqls;
    }
}

?>