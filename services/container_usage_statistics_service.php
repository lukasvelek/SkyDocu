<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerUsageStatisticsService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerUsageStatisticsService($app->logger, $app->serviceManager, $app->containerRepository, $app->containerManager);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>