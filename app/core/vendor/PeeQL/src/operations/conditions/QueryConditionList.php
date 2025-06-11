<?php

namespace PeeQL\Operations\Conditions;

/**
 * This class contains an array of conditions (QueryCondition instances)
 * 
 * @author Lukas Velek
 */
class QueryConditionList {
    /**
     * @var array<int, QueryCondition> $conditions
     */
    private array $conditions;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->conditions = [];
    }

    /**
     * Adds a condition
     * 
     * @param string $columnName Column name
     * @param string $value Value
     * @param string $type Type
     */
    public function addCondition(string $columName, string $value, string $type) {
        $this->conditions[] = new QueryCondition($columName, $value, $type);
    }

    /**
     * Adds an instance of QueryCondition
     * 
     * @param QueryCondition $condition QueryCondition instance
     */
    public function addObjectCondition(QueryCondition $condition) {
        $this->conditions[] = $condition;
    }

    /**
     * Returns an array of converted conditions
     */
    public function getConvertedConditionsAsArray(): array {
        $conditions = [];
        
        foreach($this->conditions as $condition) {
            $conditions[] = $condition->convert();
        }

        return $conditions;
    }

    /**
     * Returns an array of conditions
     */
    public function getConditions(): array {
        return $this->conditions;
    }
}

?>