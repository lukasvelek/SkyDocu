<?php

namespace App\Core\DB;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\Container\ProcessRepository;
use App\Repositories\Container\TransactionLogRepository as ContainerTransactionLogRepository;
use App\Repositories\ContainerRepository;
use App\Repositories\GroupRepository;
use App\Repositories\TransactionLogRepository;
use App\Repositories\UserRepository;
use App\Schemas\Containers\GetContainerDocumentsSchema;
use App\Schemas\Containers\GetContainerProcessSchema;
use App\Schemas\GetGroupsSchema;
use App\Schemas\Containers\GetContainerTransactionLogSchema;
use App\Schemas\GetContainersSchema;
use App\Schemas\GetTransactionLogSchema;
use App\Schemas\GetUsersSchema;
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
    private bool $isContainer;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $conn DatabaseConnection instance
     * @param Logger $logger Logger instance
     */
    public function __construct(DatabaseConnection $conn, Logger $logger, bool $isContainer = false) {
        $this->conn = $conn;
        $this->logger = $logger;
        $this->isContainer = $isContainer;

        $this->repositoryParams = [$this->conn, $this->logger];

        $this->peeql = new PeeQLPeeQL();

        $this->defineRoutes();
        $this->defineSchema();
    }

    /**
     * Defines schema
     */
    private function defineSchema() {
        $schema = $this->peeql->getSchema();

        if($this->isContainer) {
            $schema->addSchema(GetContainerTransactionLogSchema::class, 'GetTransactionLogSchema');
            $schema->addSchema(GetContainerProcessSchema::class, 'GetProcessesSchema');
            $schema->addSchema(GetContainerDocumentsSchema::class, 'GetDocumentsSchema');
        } else {
            $schema->addSchema(GetContainersSchema::class, 'GetContainersSchema');
            $schema->addSchema(GetUsersSchema::class, 'GetUsersSchema');
            $schema->addSchema(GetTransactionLogSchema::class, 'GetTransactionLogSchema');
            $schema->addSchema(GetGroupsSchema::class, 'GetGroupsSchema');
        }
    }

    /**
     * Defines routes
     */
    private function defineRoutes() {
        $router = $this->peeql->getRouter();

        if($this->isContainer) {
            $router->addRoute('transactionLog', ContainerTransactionLogRepository::class, $this->repositoryParams);
            $router->addRoute('processes', ProcessRepository::class, $this->repositoryParams);
            $router->addRoute('documents', DocumentRepository::class, $this->repositoryParams);
        } else {
            $router->addRoute('containers', ContainerRepository::class, $this->repositoryParams);
            $router->addRoute('users', UserRepository::class, $this->repositoryParams);
            $router->addRoute('transactionLog', TransactionLogRepository::class, $this->repositoryParams);
            $router->addRoute('groups', GroupRepository::class, $this->repositoryParams);
        }
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