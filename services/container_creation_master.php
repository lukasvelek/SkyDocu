<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerCreationMasterService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerCreationMasterService($app->logger, $app->serviceManager, $app->containerRepository);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>