<?php

namespace App\Api;

use App\Core\Application;
use App\Core\Container;
use App\Core\DatabaseConnection;
use App\Core\DB\PeeQL;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Managers\Container\ExternalSystemsManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ExternalSystemLogRepository;
use App\Repositories\Container\ExternalSystemRightsRepository;
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

    protected PeeQL $peeql;

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

        $contentRepository = new ContentRepository($this->conn, $this->app->logger, $this->app->transactionLogRepository);
        $entityManager = new EntityManager($this->app->logger, $contentRepository);

        $externalSystemsRepository = new ExternalSystemsRepository($this->conn, $this->app->logger, $this->app->transactionLogRepository);
        $externalSystemLogRepository = new ExternalSystemLogRepository($this->conn, $this->app->logger, $this->app->transactionLogRepository);
        $externalSystemTokenRepository = new ExternalSystemTokenRepository($this->conn, $this->app->logger, $this->app->transactionLogRepository);
        $externalSystemRightsRepository = new ExternalSystemRightsRepository($this->conn, $this->app->logger, $this->app->transactionLogRepository);

        $this->externalSystemsManager = new ExternalSystemsManager(
            $this->app->logger,
            $entityManager,
            $externalSystemsRepository,
            $externalSystemLogRepository,
            $externalSystemTokenRepository,
            $externalSystemRightsRepository
        );

        $this->container = new Container($this->app, $this->containerId);

        $this->peeql = new PeeQL($this->conn, $this->app->logger, $this->app->transactionLogRepository, true);
    }

    /**
     * Returns key from passed data
     * 
     * @param string $key Key
     * @param bool $throw Throw exception
     * @throws GeneralException
     */
    protected function get(string $key, bool $throw = true) {
        if($this->data === null) {
            $this->loadData();
        }

        if(!array_key_exists($key, $this->data)) {
            if($throw) {
                throw new GeneralException('\'' . $key . '\' is not defined.');
            } else {
                return null;
            }
        }

        return $this->data[$key];
    }

    /**
     * Returns API result
     */
    public function getResult(): string {
        try {
            $this->startup();
        } catch(AException $e) {
            $this->setResponseCode(500);
            throw $e;
        }

        $result = $this->run();

        if(array_key_exists('error', $result->getData())) {
            $this->setResponseCode(500);
        }

        return $result->getResult();
    }

    /**
     * Sets the response code
     * 
     * @param int $code Code
     */
    protected function setResponseCode(int $code) {
        http_response_code($code);
    }

    /**
     * Processes the API call
     */
    protected abstract function run(): JsonResponse;
}

?>