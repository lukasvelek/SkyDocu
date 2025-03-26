<?php

namespace App\Api;

use App\Authenticators\ExternalSystemAuthenticator;
use App\Core\Application;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Exceptions\ApiException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
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
abstract class ABaseApiClass {
    protected Application $app;
    protected HttpRequest $request;

    protected ExternalSystemsManager $externalSystemsManager;
    protected ExternalSystemAuthenticator $externalSystemAuthenticator;

    /**
     * Class constructor
     * 
     * @param Application $app Application instance
     */
    public function __construct(Application $app) {
        $this->app = $app;

        $this->request = $app->getRequest();
    }

    /**
     * Processes the API request
     * 
     * @return JsonResponse
     */
    public abstract function run(): JsonResponse;

    /**
     * Converts exception to JSON
     * 
     * @param AException $e Exception
     */
    protected function convertExceptionToJson(AException $e): JsonResponse {
        return new JsonResponse(['hash' => $e->getHash(), 'message' => $e->getMessage(), 'stackTrace' => $e->getTraceAsString()]);
    }

    /**
     * Starts up the API backend
     * 
     * @param string $containerId Container ID
     */
    protected function startup(string $containerId) {
        try {
            $container = $this->app->containerManager->getContainerById($containerId, true);

            $conn = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

            $logger = new Logger();
            $logger->setContainerId($container->getId());

            $contentRepository = new ContentRepository($conn, $logger);
            $entityManager = new EntityManager($logger, $contentRepository);

            $externalSystemsRepository = new ExternalSystemsRepository($conn, $logger);
            $externalSystemLogRepository = new ExternalSystemLogRepository($conn, $logger);
            $externalSystemTokenRepository = new ExternalSystemTokenRepository($conn, $logger);

            $this->externalSystemsManager = new ExternalSystemsManager($logger, $entityManager, $externalSystemsRepository, $externalSystemLogRepository, $externalSystemTokenRepository);
            $this->externalSystemAuthenticator = new ExternalSystemAuthenticator($this->externalSystemsManager, $logger);
        } catch(AException $e) {
            throw new GeneralException('Could not startup the API backend.');
        }
    }

    /**
     * Returns processed POST data as an associative array
     * 
     * @return array Data
     */
    protected function getPostData() {
        $data = file_get_contents('php://input');

        if(empty($data)) {
            throw new GeneralException('No data entered.');
        }

        return json_decode($data, true)['data'];
    }

    /**
     * Returns container ID entered for authentication
     * 
     * @return string Container ID
     * @throws ApiException
     */
    protected function getContainerId() {
        if(!array_key_exists('containerId', $this->getPostData())) {
            throw new GeneralException('Container ID is not set.');
        }
            
        $containerId = $this->getPostData()['containerId'];

        if($containerId === null) {
            throw new ApiException('No container ID entered for authentication.');
        }

        return $containerId;
    }
}

?>