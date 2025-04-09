<?php

namespace PeeQL\Operations;

use PeeQL\Operations\Conditions\QueryConditionList;

/**
 * Common class for all PeeQL operations
 * 
 * @author Lukas Velek
 */
abstract class AOperation {
    public const OPERATION_TYPE_QUERY = 'query';
    public const OPERATION_TYPE_MUTATION = 'mutation';

    public string $type;

    protected string $name;
    protected ?string $description;
    protected ?string $author;
    protected string $handlerName;
    protected string $handlerMethodName;
    protected array $selectCols;
    protected array $orderBy;
    protected QueryConditionList $conditions;
    
    protected array $parsedJson;

    /**
     * Class constructor
     * 
     * @param string $type Operation type
     * @param string $name Operation name
     */
    protected function __construct(string $type, string $name) {
        $this->type = $type;
        $this->name = $name;

        $this->description = null;
        $this->author = null;
        $this->selectCols = [];
        $this->orderBy = [];
        $this->conditions = new QueryConditionList();
    }

    /**
     * Sets parsed JSON
     * 
     * @param array $parsedJson Parsed JSON
     */
    public function setData(array $parsedJson) {
        $this->parsedJson = $parsedJson;

        $this->processJson();
    }

    /**
     * Goes through the parsed JSON and sets variables
     */
    protected function processJson() {
        $this->description = $this->get('description');
        $this->author = $this->get('author');
        $this->handlerName = array_keys($this->get('definition'))[0];
        $this->handlerMethodName = array_keys($this->get('definition.' . $this->handlerName))[0];
    }

    /**
     * Returns value by key from the parsed JSON or null
     * 
     * @param $key Key
     */
    protected function get(string $key): mixed {
        if(str_contains($key, '.')) {
            // is multiple namespace

            $parts = explode('.', $key);

            $json = $this->parsedJson;

            $finished = false;
            for($i = 0; $i < count($parts); $i++) {
                if(array_key_exists($parts[$i], $json)) {
                    $json = $json[$parts[$i]];
                } else {
                    break;
                }

                if(($i + 1) == count($parts)) {
                    $finished = true;
                }
            }

            if($finished) {
                return $json;
            } else {
                return null;
            }
        } else {
            if(array_key_exists($key, $this->parsedJson)) {
                return $this->parsedJson[$key];
            } else {
                return null;
            }
        }
    }

    /**
     * Returns the handler name
     */
    public function getHandlerName(): string {
        return $this->handlerName;
    }

    /**
     * Returns the handler method name
     */
    public function getHandlerMethodName(): string {
        return $this->handlerMethodName;
    }

    /**
     * Returns an array of columns to be returned
     */
    public function getColumns(): array {
        return $this->selectCols;
    }

    /**
     * Returns conditions as an array
     */
    public function getConditionsAsArray(): array {
        return $this->conditions->getConditions();
    }

    /**
     * Returns conditions
     */
    public function getConditions(): QueryConditionList {
        return $this->conditions;
    }

    /**
     * Returns the operation name
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Creates a clone after validation
     */
    public static function cloneAfterValidation(QueryOperation $operation, array $columns, QueryConditionList $conditions) {
        $obj = new static($operation->type, $operation->name);
        $obj->setData($operation->parsedJson);
        $obj->selectCols = $columns; 
        $obj->conditions = $conditions;

        return $obj;
    }
}

?>