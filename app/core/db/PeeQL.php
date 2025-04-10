<?php

namespace App\Core\DB;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\ContainerRepository;
use App\Schemas\GetContainersSchema;
use PeeQL\PeeQL as PeeQLPeeQL;

/**
 * This class is a wrapper around the PeeQL\PeeQL vendor class. It contains schema definitions and route definitions.
 * 
 * @author Lukas Velek
 */
class PeeQL {
    private PeeQLPeeQL $peeql;
    private DatabaseConnection $conn;
    private Logger $logger;

    private array $repositoryParams;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $conn DatabaseConnection instance
     * @param Logger $logger Logger instance
     */
    public function __construct(DatabaseConnection $conn, Logger $logger) {
        $this->conn = $conn;
        $this->logger = $logger;

        $this->repositoryParams = [$this->conn, $this->logger];

        $this->peeql = new PeeQLPeeQL();

        $this->defineSchema();
        $this->defineRoutes();
    }

    /**
     * Defines schema
     */
    private function defineSchema() {
        $schema = $this->peeql->getSchema();

        $schema->addSchema(GetContainersSchema::class, 'GetContainersSchema');
    }

    /**
     * Defines routes
     */
    private function defineRoutes() {
        $router = $this->peeql->getRouter();

        $router->addRoute('containers', ContainerRepository::class, $this->repositoryParams);
    }

    /**
     * Executes the JSON query and returns the result as an associative array
     * 
     * @param array $json JSON query
     */
    public function execute(array $json): array {
        $json = json_encode($json);

        $result = $this->peeql->execute($json);

        return json_decode($result, true);
    }
}

?>