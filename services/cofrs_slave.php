<?php

use App\Exceptions\ServiceException;
use App\Services\ContainerOrphanedFilesRemovingSlaveService;

require_once('CommonService.php');

global $app;

try {
    $service = new ContainerOrphanedFilesRemovingSlaveService($app->logger, $app->serviceManager, $app->containerManager, $app->dbManager);
    $service->run();
} catch(Exception|Error $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>