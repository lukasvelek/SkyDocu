<?php

namespace PeeQL\Schema;

use Exception;

/**
 * This class is used to define schemas for different operations
 * 
 * @author Lukas Velek
 */
class PeeQLSchema {
    private array $schemas;
    private ?string $namespace;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->schemas = [];
        $this->namespace = null;
    }

    /**
     * Sets common namespace
     * 
     * @param ?string $namespace Namespace
     */
    public function setCommonNamespace(?string $namespace) {
        $this->namespace = $namespace;
    }

    /**
     * Adds a schema definition
     * 
     * @param string $className Class name
     * @param ?string $name Schema name
     */
    public function addSchema(string $className, ?string $name = null) {
        if($name === null) {
            $name = $className;
        }

        if($this->namespace !== null) {
            $className = $this->namespace . $className;
        }

        try {
            $obj = new $className();
        } catch(Exception $e) {
            throw new Exception(sprintf('Could not create an instance of \'%s\'.', $className), 9999, $e);
        }

        $this->addObjectSchema($name, $obj);
    }

    /**
     * Adds a schema definition using schema object
     * 
     * @param string $name Schema name
     * @param ASchema $schema Schema object
     */
    public function addObjectSchema(string $name, ASchema $schema) {
        $this->schemas[$name] = $schema;
    }
    
    /**
     * Returns the schema
     * 
     * @param string $name Schema name
     */
    public function getSchema(string $name): ASchema {
        if(!array_key_exists($name, $this->schemas)) {
            throw new Exception(sprintf('Schema named \'%s\' does not exist.', $name), 9999);
        }

        return $this->schemas[$name];
    }
}

?>