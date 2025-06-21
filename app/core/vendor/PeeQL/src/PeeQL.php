<?php

namespace PeeQL;

use Exception;
use PeeQL\Parser\PeeQLParser;
use PeeQL\Router\PeeQLRouter;
use PeeQL\Schema\PeeQLSchema;

/**
 * This is the main PeeQL class that has definitions of all important methods and functions
 * 
 * @author Lukas Velek
 */
class PeeQL {
    private ?PeeQLRouter $router;
    private ?PeeQLSchema $schema;
    private PeeQLParser $parser;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->parser = new PeeQLParser();
        
        $this->schema = null;
        $this->router = null;
    }

    /**
     * Returns an existing or a new instance of PeeQLRouter for route definition
     */
    public function getRouter(): PeeQLRouter {
        if($this->router === null) {
            $this->router = new PeeQLRouter();
        }

        $router = &$this->router;

        return $router;
    }

    /**
     * Processes the given JSON $json query, executes it and returns the result
     * 
     * @param string $json JSON query
     */
    public function execute(string $json): mixed {
        if($this->router === null) {
            throw new Exception('No routes are defined.', 9999);
        }
        if($this->schema === null) {
            throw new Exception('No schemas are defined.', 9999);
        }
        return $this->parser->parse($this->router, $this->schema, $json);
    }

    /**
     * Returns an existing or a new instance of PeeQLSchema for schema definition
     */
    public function getSchema(): PeeQLSchema {
        if($this->schema === null) {
            $this->schema = new PeeQLSchema();
        }

        $schema = &$this->schema;

        return $schema;
    }
}

?>