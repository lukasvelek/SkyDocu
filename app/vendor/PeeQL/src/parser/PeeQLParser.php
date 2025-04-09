<?php

namespace PeeQL\Parser;

use Exception;
use PeeQL\Operations\QueryOperation;
use PeeQL\Result\AResult;
use PeeQL\Router\PeeQLRouter;
use PeeQL\Schema\PeeQLSchema;

/**
 * This class is responsible for parsing JSON to understandable sections for the rest of the component
 * 
 * @author Lukas Velek
 */
class PeeQLParser {
    /**
     * Class constructor
     */
    public function __construct() {}

    /**
     * Parses given json and returns the result of the handler
     * 
     * @param PeeQLRouter $router Router
     * @param PeeQLSchema $schema Schema
     * @param string $json JSON
     */
    public function parse(PeeQLRouter $router, PeeQLSchema $schema, string $json): mixed {
        $decodedJson = json_decode($json, true);

        if(!array_key_exists('operation', $decodedJson)) {
            throw new Exception(sprintf('Attribute \'%s\' is not defined in the JSON query.', 'operation'), 9999);
        }

        $operation = $decodedJson['operation'];

        if($operation == 'query') {
            $operation = $this->createQueryOperation($decodedJson);
        } else {
            throw new Exception(sprintf('Operation of type \'%s\' is unknown.', $operation));
        }

        $schemaName = ucfirst($operation->getName()) . 'Schema';

        $_schema = $schema->getSchema($schemaName);
        $operation = $_schema->validate($operation);

        $handler = $router->route($operation->getHandlerName());
        
        try {
            if(!method_exists($handler, $operation->getHandlerMethodName())) {
                throw new Exception(sprintf('No method \'%s\' exists in \'%s\'.', $operation->getHandlerMethodName(), $operation->getHandlerName()), 9999);
            }

            $method = $operation->getHandlerMethodName();

            $result = $handler->$method($operation);

            if(!($result instanceof AResult)) {
                throw new Exception('Handler returned value that is not a descendant of AResult.', 9999);
            }

            return $result->getResult();
        } catch(Exception $e) {
            throw new Exception('Could not parse given JSON query.', 9999, $e);
        }
    }

    /**
     * Creates a operation of type Query
     * 
     * @param array $decodedJson Decoded JSON
     */
    private function createQueryOperation(array $decodedJson): QueryOperation {
        if(!array_key_exists('name', $decodedJson)) {
            throw new Exception(sprintf('Attribute \'%s\' is not defined in the JSON query.', 'name'), 9999);
        }
        
        $queryOperation = new QueryOperation($decodedJson['name']);
        $queryOperation->setData($decodedJson);

        return $queryOperation;
    }
}

?>