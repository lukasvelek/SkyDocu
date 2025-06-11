<?php

namespace App\Core\DB\Helpers\Schema;

/**
 * Creates a new table schema
 * 
 * @author Lukas Velek
 */
class CreateTableSchema extends ABaseTableSchema {
    private array $columns = [];
    private array $indexes = [];
    private array $defaults = [];

    /**
     * Adds a column definition
     * 
     * @param string $name Column name
     * @param string $definition Column definition
     */
    private function addColumn(string $name, string $definition): static {
        $this->columns[$name] = $definition;

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
     * Adds a PRIMARY KEY column
     * 
     * @param string $name Column name
     */
    public function primaryKey(string $name): static {
        return $this->addColumn($name, 'VARCHAR(256) NOT NULL PRIMARY KEY');
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
        return 'index_' . $this->name . '_' . (count($this->indexes) + 1);
    }

    /**
     * Adds an index
     * 
     * @param array $columns Array of columns
     */
    public function index(array $columns): static {
        $this->indexes[$this->generateIndexName()] = $columns;

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

        $this->defaults[$name] = $value;

        return $this;
    }

    public function getSQL(): array {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->name . ' (';

        $columns = [];
        foreach($this->columns as $name => $definition) {
            $tmp = $name . ' ' . $definition;

            if(array_key_exists($name, $this->defaults)) {
                $tmp .= ' DEFAULT ' . $this->defaults[$name];
            }

            $columns[] = $tmp;
        }

        $sql .= implode(',', $columns) . ')';

        $sqls = [
            $sql
        ];

        foreach($this->indexes as $name => $columns) {
            $sqls[] = "DROP INDEX IF EXISTS " . $name . " ON " . $this->name;
            $sqls[] = "CREATE INDEX " . $name . " ON " . $this->name . " (" . implode(', ', $columns) . ")";
        }

        return $sqls;
    }
}

?>