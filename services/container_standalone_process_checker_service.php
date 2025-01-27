<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerStandaloneProcessCheckerService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerStandaloneProcessCheckerService($app->logger, $app->serviceManager, $app->containerManager, $app->userRepository, $app->dbManager, $app->entityManager);
    $service->run();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
} catch(Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>