<?php

namespace App\Api;

use App\Core\Application;
use App\Core\Container;
use App\Core\DatabaseConnection;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;
use App\Managers\Container\ExternalSystemsManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ExternalSystemLogRepository;
use App\Repositories\Container\ExternalSystemsRepository;
use App\Repositories\Container\ExternalSystemTokenRepository;
use App\Repositories\ContentRepository;

/**
 * Common class for all API controllers
 * 
 * @author Lukas Velek
 */
abstract class AApiClass {
    protected Application $app;
    protected ExternalSystemsManager $externalSystemsManager;
    protected DatabaseConnection $conn;
    protected Container $container;

    protected ?array $data = null;
    protected string $containerId;

    /**
     * Class constructor
     * 
     * @param Application $app Application instance
     */
    public function __construct(Application $app) {
        $this->app = $app;
    }

    /**
     * Loads data from POST
     */
    private function loadData() {
        $data = file_get_contents('php://input');

        if(empty($data)) {
            throw new GeneralException('No data entered.');
        }

        $this->data = json_decode($data, true)['data'];
    }

    /**
     * Starts up the API backend
     */
    protected function startup() {
        if($this->data === null) {
            $this->loadData();
        }

        $container = $this->app->containerManager->getContainerById($this->containerId, true);
        
        $this->conn = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

        $contentRepository = new ContentRepository($this->conn, $this->app->logger);
        $entityManager = new EntityManager($this->app->logger, $contentRepository);

        $externalSystemsRepository = new ExternalSystemsRepository($this->conn, $this->app->logger);
        $externalSystemLogRepository = new ExternalSystemLogRepository($this->conn, $this->app->logger);
        $externalSystemTokenRepository = new ExternalSystemTokenRepository($this->conn, $this->app->logger);

        $this->externalSystemsManager = new ExternalSystemsManager(
            $this->app->logger,
            $entityManager,
            $externalSystemsRepository,
            $externalSystemLogRepository,
            $externalSystemTokenRepository
        );

        $this->container = new Container($this->app, $this->containerId);
    }

    /**
     * Returns key from passed data
     * 
     * @param string $key Key
     * @throws GeneralException
     */
    protected function get(string $key) {
        if($this->data === null) {
            $this->loadData();
        }

        if(!array_key_exists($key, $this->data)) {
            throw new GeneralException('\'' . $key . '\' is not defined.');
        }

        return $this->data[$key];
    }

    /**
     * Returns API result
     */
    public function getResult(): string {
        $this->startup();

        $result = $this->run();

        return $result->getResult();
    }

    /**
     * Processes the API call
     */
    protected abstract function run(): JsonResponse;
}

?>