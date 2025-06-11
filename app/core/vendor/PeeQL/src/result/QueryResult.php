<?php

namespace PeeQL\Result;

/**
 * QueryResult represents a result of operation of type query
 * 
 * @author Lukas Velek
 */
class QueryResult extends AResult {
    private array $data;

    public function __construct() {
        parent::__construct();

        $this->data = [];
    }

    /**
     * Sets the result data
     * 
     * @param array $data Data
     */
    public function setResultData(array $data) {
        $this->data = $data;
    }
    
    protected function processResult(): array {
        $data = [];

        if($this->error) {
            $data['error'] = 1;
            $data['errorMessage'] = $this->errorMessage;
        } else {
            $data['data'] = $this->data;
        }

        return $data;
    }
}

?>