<?php

namespace PeeQL\Schema;

use PeeQL\Operations\AOperation;
use PeeQL\Operations\Conditions\QueryConditionList;
use PeeQL\Operations\QueryOperation;

/**
 * Common class for query schema definition classes
 * 
 * @author Lukas Velek
 */
abstract class AQuerySchema extends ASchema {
    protected array $visibleColumns;
    protected array $filterableColumns;
    protected array $sortableColumns;
    protected array $requiredFilterColumns;

    public function __construct(string $name) {
        parent::__construct($name);

        $this->visibleColumns = [];
        $this->filterableColumns = [];
        $this->sortableColumns = [];
        $this->requiredFilterColumns = [];
    }

    /**
     * Adds a required filter column
     * 
     * If the query doesn't have a condition with this column, it will fail
     * 
     * @param string $name Column name
     */
    public function addRequiredFilterColumn(string $name) {
        $this->requiredFilterColumns[] = $name;
    }

    /**
     * Adds column to schema
     * 
     * @param string $name Column name
     * @param bool $filterable Is column filterable?
     * @param bool $sortable Is column sortable?
     */
    protected function addColumn(string $name, bool $filterable = true, bool $sortable = true) {
        $this->visibleColumns[] = $name;
        if($filterable) {
            $this->filterableColumns[] = $name;
        }
        if($sortable) {
            $this->sortableColumns[] = $name;
        }
    }

    /**
     * Adds multiple columns to schema and implicitly allows filtering and sorting for them
     * 
     * @param array $columnNames Column names
     */
    protected function addMultipleColumns(array $columnNames) {
        foreach($columnNames as $column) {
            $this->addColumn($column);
        }
    }

    /**
     * Removes a column from filterable columns
     * 
     * @param string $name Column name
     */
    protected function setNotFilterableColumn(string $name) {
        $_index = null;
        foreach($this->filterableColumns as $index => $column) {
            if($column == $name) {
                $_index = $index;
            }
        }

        if($_index !== null) {
            unset($this->filterableColumns[$_index]);
        }
    }

    /**
     * Removes a column from sortable columns
     * 
     * @param string $name Column name
     */
    protected function setNotSortableColumn(string $name) {
        $_index = null;
        foreach($this->sortableColumns as $index => $column) {
            if($column == $name) {
                $_index = $index;
            }
        }

        if($_index !== null) {
            unset($this->sortableColumns[$_index]);
        }
    }

    public function createSchemaForBrowsing(): string {
        $schema = [
            'name' => $this->name,
            'visibleColumns' => $this->visibleColumns,
            'filterableColumns' => $this->filterableColumns,
            'sortableColumns' => $this->sortableColumns,
            'requiredColumnsForFiltering' => $this->requiredFilterColumns
        ];

        return json_encode($schema);
    }

    public function validate(AOperation $operation): AOperation {
        if(!$this->isDefined) {
            $this->define();
        }

        $visibleColumns = [];
        foreach($operation->getColumns() as $column) {
            if(in_array($column, $this->visibleColumns)) {
                $visibleColumns[] = $column;
            }
        }

        $conditionList = new QueryConditionList();
        foreach($operation->getConditionsAsArray() as $condition) {
            /**
             * @var \PeeQL\Operations\Conditions\QueryCondition $condition
             */

            if(in_array($condition->getColumnName(), $this->filterableColumns)) {
                $conditionList->addObjectCondition($condition);
            }
        }

        return QueryOperation::cloneAfterValidation($operation, $visibleColumns, $conditionList);
    }
}

?>