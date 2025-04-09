<?php

namespace PeeQL\Operations;

use Exception;

/**
 * QueryOperation defines operation of type Query
 * 
 * @author Lukas Velek
 */
class QueryOperation extends AOperation {
    private ?int $limit;
    private ?int $page;

    /**
     * Class constructor
     * 
     * @param string $name Query name
     */
    public function __construct(string $name) {
        parent::__construct(self::OPERATION_TYPE_QUERY, $name);

        $this->limit = null;
        $this->page = null;
    }
    
    protected function processJson() {
        parent::processJson();

        $path = sprintf('definition.%s.%s', $this->handlerName, $this->handlerMethodName);

        $this->selectCols = $this->get($path . '.cols');
        
        // Conditions
        $conditions = $this->get($path . '.conditions');

        if($conditions !== null) {
            foreach($conditions as $condition) {
                $this->conditions->addCondition($condition['col'], $condition['value'], $condition['type']);
            }
        }

        // Ordering
        $orderBy = $this->get($path . '.orderBy');

        if($orderBy !== null) {
            foreach($orderBy as $key => $value) {
                $this->orderBy[$key] = $value;
            }
        }

        // Limit
        $limit = $this->get($path . '.limit');

        if($limit !== null) {
            $this->limit = $limit;
        }

        // Page
        $page = $this->get($path . '.page');

        if($page !== null) {
            $this->page = $page;
        }

        if(($this->page === null && $this->limit !== null) || ($this->page !== null && $this->limit === null)) {
            throw new Exception('If limit is set, then page must be set and vice versa.', 9999);
        }
    }

    /**
     * Returns the limit
     */
    public function getLimit(): ?int {
        return $this->limit;
    }

    /**
     * Returns the page
     */
    public function getPage(): ?int {
        return $this->page;
    }
}

?>