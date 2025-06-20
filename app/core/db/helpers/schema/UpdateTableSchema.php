<?php

namespace App\Core\DB\Helpers\Schema;

class UpdateTableSchema extends ABaseTableSchema {
    private array $addColumns = [];
    private array $addIndexes = [];
    private array $addDefaults = [];
    private array $removeColumns = [];
    private array $modifyColumns = [];

    /**
     * Adds a column definition
     * 
     * @param string $name Column name
     * @param string $definition Column definition
     */
    private function addColumn(string $name, string $definition): static {
        $this->addColumns[$name] = $definition;

        return $this;
    }

    /**
     * Adds a nullable column definition
     * 
     * @param string $name Column name
     * @param string $definition Column definition
     * @param bool $isNull Is column nullable
     */
    private function addNullableColumn(string $name, string $definition, bool $isNull): static {
        if(!$isNull) {
            $definition .= ' NOT';
        }

        $definition .= ' NULL';

        return $this->addColumn($name, $definition);
    }

    /**
     * Adds a FOREIGN KEY column that also is indexed
     * 
     * @param string $name Column name
     */
    public function foreignKey(string $name): static {
        $this->varchar($name, 256);
        return $this->index([$name]);
    }

    /**
     * Adds a BIT column
     * 
     * @param string $name Column name
     * @param bool $isNull Is column nullable
     */
    public function bit(string $name, bool $isNull = false): static {
        return $this->integer($name, 1, $isNull);
    }

    /**
     * Adds an ENUM column
     * 
     * @param string $name Column name
     * @param bool $isNull Is column nullable
     */
    public function enum(string $name, bool $isNull = false): static {
        return $this->integer($name, 4, $isNull);
    }

    /**
     * Adds a VARCHAR column
     * 
     * @param string $name Column name
     * @param int $length VARCHAR length
     * @param bool $isNull Is column nullable
     */
    public function varchar(string $name, int $length = 256, bool $isNull = false): static {
        return $this->addNullableColumn($name, "VARCHAR($length)", $isNull);
    }

    /**
     * Adds a TEXT column
     * 
     * @param string $name Column name
     * @param bool $isNull Is column nullable
     */
    public function text(string $name, bool $isNull = false): static {
        return $this->addNullableColumn($name, "TEXT", $isNull);
    }

    /**
     * Adds an INT column
     * 
     * @param string $name Column name
     * @param int $length INT length
     * @param bool $isNull Is column nullable
     */
    public function integer(string $name, int $length = 32, bool $isNull = false): static {
        return $this->addNullableColumn($name, "INT($length)", $isNull);
    }

    /**
     * Adds an INT column for boolean data
     * 
     * @param string $name Column name
     * @param bool $isNull Is column nullable
     */
    public function bool(string $name, bool $isNull = false): static {
        return $this->integer($name, 2, $isNull);
    }

    /**
     * Adds a DATETIME column
     * 
     * @param string $name Column name
     * @param bool $isNull Is column nullable
     */
    public function datetime(string $name, bool $isNull = false): static {
        return $this->addNullableColumn($name, 'DATETIME', $isNull);
    }

    /**
     * Adds a DATETIME column with current_timestamp() as default value
     * 
     * @param string $name Column name
     */
    public function datetimeAuto(string $name): static {
        $this->addNullableColumn($name, 'DATETIME', false);
        return $this->default($name, 'current_timestamp()');
    }

    /**
     * Generates index name based on the number of existing indexes
     */
    private function generateIndexName(): string {
        return $this->name . '_' . (count($this->addIndexes) + 1);
    }

    /**
     * Adds an index
     * 
     * @param array $columns Array of columns
     */
    public function index(array $columns): static {
        $this->addIndexes[$this->generateIndexName()] = $columns;

        return $this;
    }

    /**
     * Adds a default value for given column
     * 
     * @param string $name Column name
     * @param mixed $value Default value
     */
    public function default(string $name, mixed $value): static {
        if(is_bool($value)) {
            $value = ($value ? 1 : 0);
        }

        if($value != 'current_timestamp()') {
            $value = '\'' . $value . '\'';
        }

        $this->addDefaults[$name] = $value;

        return $this;
    }

    /**
     * Removes a column from table schema
     * 
     * @param string $name Column name
     */
    public function removeColumn(string $name): static {
        $this->removeColumns[] = $name;

        return $this;
    }

    /**
     * Modifies a columns in table schema
     * 
     * @param string $name Column name
     * @param string $newDefinition New column definition
     */
    public function modifyColumn(string $name, string $newDefinition): static {
        $this->modifyColumns[$name] = $newDefinition;

        return $this;
    }

    public function getSQL(): array {
        $sqls = [];

        // drop columns
        foreach($this->removeColumns as $name) {
            $sqls[] = "ALTER TABLE " . $this->name . " DROP COLUMN " . $name;
        }

        // create columns
        foreach($this->addColumns as $name => $definition) {
            if(array_key_exists($name, $this->addDefaults)) {
                $definition .= ' DEFAULT ' . $this->addDefaults[$name];
            }
            $sqls[] = "ALTER TABLE " . $this->name . " ADD " . $name . " " . $definition;
        }
        
        // modify columns
        foreach($this->modifyColumns as $name => $newDefinition) {
            $sqls[] = "ALTER TABLE " . $this->name . " MODIFY COLUMN " . $name . " " . $newDefinition;
        }

        return $sqls;
    }
}

?>