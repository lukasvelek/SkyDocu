<?php

namespace PeeQL\Operations\Conditions;

use Exception;

/**
 * QueryCondition describes a single query condition
 * 
 * @author Lukas Velek
 */
class QueryCondition {
    /**
     * Equals
     */
    public const TYPE_EQ = 'eq';
    /**
     * Not equals
     */
    public const TYPE_NEQ = 'neq';
    /**
     * Greater than
     */
    public const TYPE_GT = 'gt';
    /**
     * Greater than or equals
     */
    public const TYPE_GTE = 'gte';
    /**
     * Less than
     */
    public const TYPE_LT = 'lt';
    /**
     * Less than or equals
     */
    public const TYPE_LTE = 'lte';

    private string $columnName;
    private mixed $value;
    private string $type;

    /**
     * Class constructor
     * 
     * @param string $columnName Column name
     * @param mixed $value Value
     * @param string $type Condition type
     */
    public function __construct(string $columnName, mixed $value, string $type) {
        $this->columnName = $columnName;
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Converts the condition to SQL format
     */
    public function convert(): string {
        $text = $this->columnName . ' ';

        switch($this->type) {
            case self::TYPE_EQ:
                $text .= '=';
                break;
            case self::TYPE_GT:
                $text .= '>';
                break;
            case self::TYPE_GTE:
                $text .= '>=';
                break;
            case self::TYPE_LT:
                $text .= '<';
                break;
            case self::TYPE_LTE:
                $text .= '<=';
                break;
            case self::TYPE_NEQ:
                $text .= '<>';
                break;
        }

        $text .= ' ';

        if(is_array($this->value)) {
            throw new Exception('Using arrays in conditions is not allowed.', 9999);
        }
        if(is_object($this->value)) {
            throw new Exception('Using objects in conditions is not allowed.', 9999);
        }
        if(is_bool($this->value)) {
            if($this->value) {
                $text .= '1';
            } else {
                $text .= '0';
            }
        } else {
            $text .= htmlspecialchars($this->value);
        }

        return $text;
    }

    /**
     * Returns column name
     */
    public function getColumnName(): string {
        return $this->columnName;
    }

    /**
     * Returns operation type
     */
    public function getOperation(): string {
        return $this->type;
    }

    /**
     * Returns value
     */
    public function getValue(): mixed {
        return $this->value;
    }
}

?>