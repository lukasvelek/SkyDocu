<?php

namespace App\Api;

use QueryBuilder\QueryBuilder;

/**
 * Common class for Get*Controllers
 * 
 * @author Lukas Velek
 */
abstract class AReadAPIOperation extends AAuthenticatedApiController {
    protected array $allowedProperties;

    /**
     * Sets allowed properties
     * 
     * @param array $properties Properties
     */
    protected function setAllowedProperties(array $properties) {
        $this->allowedProperties = $properties;
    }

    /**
     * Checks entered properties and eliminates disallowed ones
     * 
     * @param array $properties Entered properties
     */
    protected function processPropeties(array $properties): array {
        $goodProperties = [];

        foreach($properties as $property) {
            if(in_array($property, $this->allowedProperties)) {
                $goodProperties[] = $property;
            }
        }

        return $goodProperties;
    }

    /**
     * Appends where conditions
     * 
     * @param QueryBuilder &$qb QueryBuilder instance
     */
    protected function appendWhereConditions(QueryBuilder &$qb) {
        if(!array_key_exists('where', $this->data)) {
            return;
        }

        $sql = [];
        $params = [];
        $this->processConditions($this->get('where'), $sql);

        $text = '(' . implode('', $sql);
        $i = substr_count($text, '(');
        $j = substr_count($text, ')');
        $text .= str_repeat(')', ($i - $j));

        $qb->andWhere($text);
    }

    /**
     * Processes conditions
     * 
     * @param array $conditions Conditions
     * @param array &$sql SQL array
     * @param ?bool $isAnd True if it is AND, false if it is OR, null if it is none
     */
    private function processConditions(array $conditions, array &$sql, ?bool $isAnd = null) {
        $name = $conditions['name'];
        $value = $conditions['value'];

        $text = "$name = '$value'";

        if($isAnd === true) {
            $text = ' AND (' . $text;
        } else if($isAnd === false) {
            $text = ' OR (' . $text;
        }

        $sql[] = $text;

        if(!array_key_exists('and', $conditions) && !array_key_exists('or', $conditions)) {
            $sql[] = ')';
        }

        if(array_key_exists('and', $conditions)) {
            $this->processConditions($conditions['and'], $sql, true);
        }
        if(array_key_exists('or', $conditions)) {
            $this->processConditions($conditions['or'], $sql, false);
        }
    }

    /**
     * Processes results
     * 
     * @param array $handler Result handler - class, method
     * @param string $primaryKey Primary key
     * @param array ...$params Parameters
     */
    protected function getResults(array $handler, string $primaryKey, ...$params): array {
        $obj = $handler[0];
        $method = $handler[1];

        $results = [];
        $properties = $this->processPropeties($this->get('properties'));

        $entries = $obj->$method(...$params);

        foreach($entries as $entry) {
            $tmp = [];
            foreach($properties as $property) {
                $tmp[$property] = $entry->$property;
            }

            $results[]['row'] = $tmp;
        }

        return $results;
    }
}

?>