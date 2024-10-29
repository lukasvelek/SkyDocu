<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerCreationService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerCreationService($app->logger, $app->serviceManager, $app->containerManager, $app->containerRepository);
    $service->run();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
} catch(Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>