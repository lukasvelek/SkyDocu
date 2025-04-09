<?php

namespace PeeQL\Schema;

use PeeQL\Operations\AOperation;

/**
 * Common class for all schema definition classes
 * 
 * @author Lukas Velek
 */
abstract class ASchema {
    protected string $name;

    protected bool $isDefined;

    /**
     * Class constructor
     * 
     * @param string $name Schema name
     */
    public function __construct(string $name) {
        $this->name = $name;

        $this->isDefined = false;
    }

    /**
     * Creates schema for browsing and returns it as JSON
     */
    public abstract function createSchemaForBrowsing(): string;

    /**
     * Validates the QueryOperation against the schema and returns a validated instance
     * 
     * @param AOperation $operation Initial operation
     */
    public abstract function validate(AOperation $operation): AOperation;

    /**
     * Contains definitions of columns, conditions, etc.
     */
    protected abstract function define();
}

?>