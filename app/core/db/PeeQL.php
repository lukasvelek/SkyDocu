<?php

namespace App\Core\DB;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\Container\ProcessInstanceRepository;
use App\Repositories\ContainerRepository;
use App\Repositories\GroupRepository;
use App\Repositories\TransactionLogRepository;
use App\Repositories\UserRepository;
use App\Schemas\Containers\GetContainerDocumentsSchema;
use App\Schemas\Containers\GetContainerProcessInstanceSchema;
use App\Schemas\GetGroupsSchema;
use App\Schemas\GetContainersSchema;
use App\Schemas\GetTransactionLogSchema;
use App\Schemas\GetUsersSchema;
use PeeQL\IPeeQLWrapperClass;
use PeeQL\PeeQL as PeeQLPeeQL;

/**
 * This class is a wrapper around the PeeQL\PeeQL vendor class. It contains schema definitions and route definitions.
 * 
 * @author Lukas Velek
 */
class PeeQL implements IPeeQLWrapperClass {
    private PeeQLPeeQL $peeql;
    private DatabaseConnection $conn;
    private Logger $logger;
    private TransactionLogRepository $transactionLogRepository;

    private array $repositoryParams;
    private bool $isContainer;
    private string $userId;

    private bool $areRoutesDefined = false;
    private bool $isSchemaDefined = false;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $conn DatabaseConnection instance
     * @param Logger $logger Logger instance
     */
    public function __construct(DatabaseConnection $conn, Logger $logger, TransactionLogRepository $transactionLogRepository, bool $isContainer = false) {
        $this->conn = $conn;
        $this->logger = $logger;
        $this->isContainer = $isContainer;
        $this->transactionLogRepository = $transactionLogRepository;

        $this->repositoryParams = [$this->conn, $this->logger, $this->transactionLogRepository];

        $this->peeql = new PeeQLPeeQL();
    }

    /**
     * Sets user ID
     * 
     * @param string $userId User ID
     */
    public function setUserId(string $userId) {
        $this->userId = $userId;

        $this->repositoryParams[] = $this->userId;
    }

    /**
     * Defines schema
     */
    private function defineSchema() {
        $schema = $this->peeql->getSchema();

        if($this->isContainer) {
            $schema->addSchema(GetContainerProcessInstanceSchema::class, [
                'GetProcessInstancesSchema',
                'GetMyProcessInstancesSchema'
            ]);
            $schema->addSchema(GetContainerDocumentsSchema::class, 'GetDocumentsSchema');
        } else {
            $schema->addSchema(GetContainersSchema::class, 'GetContainersSchema');
            $schema->addSchema(GetUsersSchema::class, 'GetUsersSchema');
            $schema->addSchema(GetGroupsSchema::class, 'GetGroupsSchema');
        }

        $schema->addSchema(GetTransactionLogSchema::class, 'GetTransactionLogSchema');
    }

    /**
     * Defines routes
     */
    private function defineRoutes() {
        $router = $this->peeql->getRouter();

        if($this->isContainer) {
            $router->addRoute('processInstances', ProcessInstanceRepository::class, $this->repositoryParams);
            $router->addRoute('documents', DocumentRepository::class, $this->repositoryParams);
        } else {
            $router->addRoute('containers', ContainerRepository::class, $this->repositoryParams);
            $router->addRoute('users', UserRepository::class, $this->repositoryParams);
            $router->addRoute('groups', GroupRepository::class, $this->repositoryParams);
        }

        $router->addRoute('transactionLog', TransactionLogRepository::class, $this->repositoryParams);
    }

    /**
     * Executes the JSON query and returns the result as a JSON encoded string
     * 
     * @param string $json JSON query
     */
    public function execute(string $json): mixed {
        if(!$this->isSchemaDefined) {
            $this->defineSchema();
            $this->isSchemaDefined = true;
        }
        if(!$this->areRoutesDefined) {
            $this->defineRoutes();
            $this->areRoutesDefined = true;
        }

        $result = $this->peeql->execute($json);

        return $result;
    }
}

?>