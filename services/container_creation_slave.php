<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerCreationSlaveService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerCreationSlaveService($app->logger, $app->serviceManager, $app->containerManager, $app->containerRepository);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>